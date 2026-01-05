<?php

namespace App\Controller\Admin;

use App\Entity\Download;
use App\Entity\Entry;
use App\Form\EntryType;
use App\Repository\DownloadRepository;
use App\Repository\EntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;
use UAParser\Exception\FileNotFoundException;
use UAParser\Parser as UAParser;

final class FilesController extends AbstractController
{

    public function __construct
    (
        private EntityManagerInterface $em,
        private EntryRepository $entriesRepo,
    )
    {
    }

    #[Route('/files', name: 'pico_admin_files')]
    public function index(): Response
    {
        return $this->render('admin/files/index.html.twig', [
            'pageTitle' => 'Files',
            'pageDesc' => 'Manage uploaded files',
            'allowAddNewEntry' => false,
        ]);
    }

    public function filesList(RequestStack $requestStack): Response
    {
        $resp = new Response(null);

        $files = $this->entriesRepo->findBy([], ['createdAt' => 'desc']);

        return $this->render('admin/files/_crud_table_files.html.twig', [
            'paginatedResults' => false === empty($files) ? $files : false,
            'showPagination' => false,
        ], $resp);
    }

    #[Route('/files/{uniqId}/info', name: 'pico_admin_files_info')]
    public function fileInfo(Ulid $uniqId, DownloadRepository $downloadRepo): Response
    {
        $file = $this->getEntryOrPanicWith404($uniqId);

        //$downloadsCount = $downloadRepo->getDownloadsCount($file);
        $downloadsCount = $downloadRepo->countByEntry($file);

        $twigCtx = [
            'pageTitle' => 'File Info',
            'foundFile' => $file,
            'entryUniqId' => $file->getUniqLinkId()->toBase58(),
            'downloadsCount' => $downloadsCount,
        ];

        return $this->render('admin/files/single_file_info.html.twig', $twigCtx);
    }

    #[Route('/files/{uniqId}/edit', name: 'pico_admin_files_edit')]
    public function fileEdit(Request $request ,Ulid $uniqId): Response
    {
        $file = $this->getEntryOrPanicWith404($uniqId);

        $editForm = $this->createForm(EntryType::class, $file, [
            'method' => Request::METHOD_POST,
            'action' => $this->generateUrl('pico_admin_files_edit', ['uniqId' => $file->getUniqLinkId()->toBase58()]),
        ]);

        $resp = new Response();
        if($request->isMethod(Request::METHOD_POST))
        {
            $editForm->handleRequest($request);

            if( $editForm->isSubmitted() )
            {
                if( $editForm->isValid())
                {
                    $hasExpirationDate = $editForm->get(EntryType::FIELD_EXPIRES_AFTER)?->getData();
                    if( false === $hasExpirationDate )
                    {
                        $file->setExpiresAt(null);
                    }
                    else
                    {
                        $newExpirationDate = \DateTime::createFromInterface($file->getExpiresAt());
                        // set it to 23:59:59 of previous date
                        $file->setExpiresAt( $newExpirationDate->modify('midnight -1 second') );
                    }

                    $this->em->flush();

                    $this->addFlash('success', 'File updated!');
                    return $this->redirectToRoute('pico_admin_files');
                }

                $resp->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $twigCtx = [
            'pageTitle' => 'Edit File',
            'editForm' => $editForm->createView(),
            'foundFile' => $file,
            'entryUniqId' => $file->getUniqLinkId()->toBase58(),
        ];

        return $this->render('admin/files/single_file_edit.html.twig', $twigCtx, $resp);
    }

    #[Route('/files/{uniqId}/downloads', name: 'pico_admin_files_downloads')]
    public function fileDownloads(Request $request, Ulid $uniqId, DownloadRepository $downloadRepo): Response
    {
        $file = $this->getEntryOrPanicWith404($uniqId);

        //TODO: implement uniq IPs only filter
        $uniqIPsOnly = ($request->query->get('unique') !== null);

        $downloadsHistory = $downloadRepo->findBy([
            'entry' => $file,
        ]);

        $hasDownloads = !empty($downloadsHistory);
        if ($hasDownloads) {
            $uaDataForEach = $this->prepareUserAgentData($downloadsHistory);
        }

        $twigCtx = [
            'pageTitle' => 'Downloads',
            'foundFile' => $file,
            'downloadsHistory' => $downloadsHistory,
            'hasDownloads' => $hasDownloads,
            'uaDataForEach' => $uaDataForEach ?? null,
        ];
        return $this->render('admin/files/single_file_downloads.html.twig', $twigCtx);
    }

    #[Route('/files/{uniqId}/confirm-delete', name: 'pico_admin_files_delete_confirm', methods: ['GET', 'DELETE'])]
    public function confirmDelete(Request $request,Ulid $uniqId): Response
    {
        $file = $this->getEntryOrPanicWith404($uniqId);

        $deleteForm = $this->createDeleteForm($uniqId);
        $deleteForm->handleRequest($request);
        $resp = new Response();
        if( $deleteForm->isSubmitted() )
        {
            if( $deleteForm->isValid() )
            {
                $this->entriesRepo->doDeleteEntryAndAllDataChunks($file, true);
                $this->addFlash('success', 'File deleted');
                return $this->redirectToRoute('pico_admin_files');
            }

            $resp->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $twigCtx = [
            'pageTitle' => 'Delete File',
            'foundFile' => $file,
            'entryUniqId' => $file->getUniqLinkId()->toBase58(),
            'deleteForm' => $deleteForm?->createView(),
        ];

        return $this->render('admin/files/single_file_confirm_delete.html.twig', $twigCtx, $resp);
    }

    private function getEntryOrPanicWith404(Ulid $uniqId): ?Entry
    {
        $file = $this->entriesRepo->findOneBy(['uniqLinkId' => $uniqId]);
        if ($file === null) {
            throw $this->createNotFoundException('File not found!');
        }
        return $file;
    }

    /**
     * TODO: Move somewhere else!
     *
     * @param list<Download> $downloadsHistory
     * @return array<int, array{os: string, ver: string}>
     * @throws FileNotFoundException
     */
    private function prepareUserAgentData(array $downloadsHistory): array
    {
        $uaParser = UAParser::create();
        $data = [];
        foreach ($downloadsHistory as $historyData) {
            $ua = $historyData->getUserAgent();
            if (!empty($ua)) {
                $parsed = $uaParser->parse($historyData->getUserAgent());
                $data[$historyData->getId()] = [
                    'os' => $parsed->os->family ?? '-unknown-',
                    'ver' => $parsed->ua->toString(),
                ];
            }
        }

        return $data;
    }

    private function createDeleteForm(Ulid $uniqId): \Symfony\Component\Form\FormInterface
    {
        $form = $this->createFormBuilder(null, [
            'method' => Request::METHOD_DELETE,
            'action' => $this->generateUrl('pico_admin_files_delete_confirm', [
                'uniqId' => $uniqId->toBase58(),
                'confirmed' => '✓']),
        ]);

        $form->add('entryId', HiddenType::class, [
            'label' => false,
            'data' => $uniqId->toBase58(),
        ]);

        $form->add('submit', SubmitType::class, [
            'label' => 'Delete',
        ]);

        return $form->getForm();
    }
}
