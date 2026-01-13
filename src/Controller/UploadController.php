<?php

namespace App\Controller;

use App\Entity\Entry;
use App\Entity\EntryChunk;
use App\Entity\GuestLink;
use App\Form\UploadFormType;
use App\Repository\EntryRepository;
use App\Repository\GuestLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Ulid;

final class UploadController extends AbstractController
{
    private const UPLOAD_SUCCESS_MARK = '✓';
    private const SESSION_KEY_PREV_UPLOADED = 'pico/uploaded';
    private const SESSION_KEY_GUEST_LINK_ID = 'pico/guest_link_id';

    public function __construct
    (
        private EntityManagerInterface $em,
        private GuestLinkRepository    $guestLinksRepo,
        private EntryRepository        $entriesRepo,
    )
    {
    }

    #[Route('/g/{guestLinkUniqId}', name: 'pico_upload_file_with_guest_link', methods: ['GET', 'POST', 'PUT'])]
    public function guestLinkUpload(RequestStack $requestStack, SluggerInterface $slugger, Ulid $guestLinkUniqId, bool $inline_form = false): Response
    {
        $request = $requestStack->getCurrentRequest();
        $foundGuestLink = $this->guestLinksRepo->findOneBy(['uniqLinkId' => $guestLinkUniqId /*, 'disabled' => false*/]);
        // TODO:
        // - is active?
        // - is expired?
        // - upload-limit reached?

        if ($foundGuestLink === null) {
            throw $this->createNotFoundException('Guest Link is not found!');
        }

        $request->getSession()->set(self::SESSION_KEY_GUEST_LINK_ID, $foundGuestLink->getUniqLinkId());

        return $this->upload($requestStack, $slugger, $foundGuestLink, $inline_form);
    }


    /*
     * FIXME: This method needs refactoring
     */
    #[Route('/upload', name: 'pico_upload_file', methods: ['POST', 'PUT', 'GET'])]
    public function upload(RequestStack $requestStack, SluggerInterface $slugger, ?GuestLink $guestLink = null, bool $inline_form = false): Response
    {
        $requests = $requestStack->getCurrentRequest();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isSubrequest = $requestStack->getParentRequest() !== null;

        $session = $requests->getSession();
        if ($session->has(self::SESSION_KEY_GUEST_LINK_ID)) {
            $guestLinkUniqId = $session->get(self::SESSION_KEY_GUEST_LINK_ID);
            $session->remove(self::SESSION_KEY_GUEST_LINK_ID); // delete immediately
            /** @var GuestLink|null $guestLink */
            $guestLink = $this->guestLinksRepo->findOneBy(['uniqLinkId' => $guestLinkUniqId]);
            // if we have found one, take uniqId
            $guestLinkUniqId = $guestLink?->getUniqLinkId()->toBase58();
        }
        else
        {
            $guestLink = null;
            $guestLinkUniqId = null;
        }

        if ($guestLink === null && $isAdmin === false) {
            throw $this->createNotFoundException('Page not found!');
        }


        $uploadForm = null;
        if( $guestLink !== null && $guestLink->isExpired( new \DateTimeImmutable('now') ) )
        {
            $this->addFlash('error', 'This upload link is no longer active. Contact the service owner to get a new link.');
        }
        else
        {
            $maxUploadFilesize = null;
            if ($guestLink !== null && $guestLink->isUnlimitedFileSize() === false) {
                $maxUploadFilesize = $guestLink->getMaxFileSizeInMegaBytes(); // as integer
            }
            $uploadForm = $this->createForm(UploadFormType::class, null, [
                'is_guest_link' => $guestLink !== null,
                'preselected_auto_expire_value' => $guestLink?->getFileExpiration(),
                'custom_max_upload_filesize_in_mb' => $maxUploadFilesize,
                'action' => $guestLink === null ? $this->generateUrl('pico_upload_file') : $this->generateUrl('pico_upload_file_with_guest_link', ['guestLinkUniqId' => $guestLinkUniqId]),
                'method' => Request::METHOD_POST,
            ]);
        }

        if (null === $guestLink && $isAdmin === false) {
            //TODO: 403 if ajax/subrequest?
            $this->createAccessDeniedException();
        }

        $resp = new Response(null);
        $twigCtx = [
            'form' => $uploadForm?->createView(),
            'showSubmitBtn' => true,
            'showProgressBar' => false,
            'pageTitle' => $isAdmin || $guestLink !== null ? 'Upload' : null,
            'guestLinkUniqId' => $guestLink?->getUniqLinkId()->toBase58(),
        ];

        $redirRoute = 'pico_upload_file';
        $routeParams = [];
        if ($guestLink !== null) {
            $routeParams['guestLinkUniqId'] = $guestLinkUniqId;
            $redirRoute = 'pico_upload_file_with_guest_link';
        }

        // file was uploaded, just show the links
        if ($requests->isMethod(Request::METHOD_GET) && $requests->query->has('uploaded')) {

            // no data in session about prev upload? then redirect back to /upload as if it was a "fresh start"
            if (null === $prevUploadedFileMetadata = $session->get(self::SESSION_KEY_PREV_UPLOADED)) {
                return $this->redirectToRoute($redirRoute, $routeParams);
            }

            $session->remove(self::SESSION_KEY_PREV_UPLOADED);
            $twigCtx = array_merge([
                'prevUploadSucceed' => $requests->query->has('uploaded'),
                'entryUniqId' => $prevUploadedFileMetadata['entryUniqId'],
                'entryFilename' => $prevUploadedFileMetadata['entryFilename'],
            ], $twigCtx);
        } elseif ($requests->isMethod(Request::METHOD_POST)) {
            $uploadForm->handleRequest($requests);

            if ($uploadForm->isSubmitted()) {
                if ($uploadForm->isValid()) {
                    /** @var UploadedFile $uploadedFile */
                    $uploadedFile = $uploadForm->get('file')->getData();
                    $note = $uploadForm->has('note') ? $uploadForm->get('note')->getData() : null;
                    if ($uploadedFile) {
                        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);

                        $entry = (new Entry())
                            ->setUniqLinkId(new Ulid())
                            ->setNote($note)
                            ->setSize($uploadedFile->getSize())
                            ->setFilename($originalFilename . '.' . $uploadedFile->getClientOriginalExtension())
                            ->setSafeFilename($safeFilename . '.' . $uploadedFile->getClientOriginalExtension())
                            ->setContentType($uploadedFile->getMimeType())
                            ->setGuestLink(null);

                        // normalizing fileExpirationDate
                        $chosenFileExpiration = $uploadForm->get('auto_expire')?->getData();
                        // null -> never or not available
                        if( $chosenFileExpiration !== null )
                        {
                            $refNow = new \DateTimeImmutable('today');
                            $expirationModifier = str_replace('-', ' ', $chosenFileExpiration);
                            $newExpirationDateTime = $refNow->modify($expirationModifier);
                            $entry->setExpiresAt($newExpirationDateTime);
                        }


                        $this->em->beginTransaction();
                        $success = false;
                        try {
                            $contentsStrm = new Stream($uploadedFile->getPathname());
                            $dataChunk = (new EntryChunk())
                                ->setEntry($entry)
                                ->setDataChunk($contentsStrm->getContent());

                            $this->em->persist($entry);
                            $this->em->persist($dataChunk);
                            //
                            if ($guestLink !== null) {
                                $guestLink->getId() === null and $this->em->persist($guestLink);
                                $entry->setGuestLink($guestLink);
                                $this->updateCurrentUploadsCounter($guestLink);
                            }
                            $this->em->flush();
                            $this->em->commit();
                            $success = true;
                        } catch (\Throwable $err) {
                            $this->em->rollback();
                            throw $err;
                        } finally {
                            unset($contentsStrm, $dataChunk);
                        }


                        if ($success) {
                            $session->remove(self::SESSION_KEY_GUEST_LINK_ID);
                            $session->remove(self::SESSION_KEY_PREV_UPLOADED);
                            $this->addFlash('success', 'Upload complete!');

                            $routeParams = ['uploaded' => self::UPLOAD_SUCCESS_MARK];
                            if ($guestLink !== null) {
                                $routeParams['guestLinkUniqId'] = $guestLinkUniqId;
                            }

                            $entryUniqId = $entry->getUniqLinkId()->toBase58();
                            $session->set(self::SESSION_KEY_PREV_UPLOADED, [
                                'entryUniqId' => $entryUniqId,
                                'entryFilename' => $entry->getSafeFilename(),
                                'shortLink' => $this->generateUrl('pico_download_entry_short', ['uniqId' => $entryUniqId]),
                                'longLink' => $this->generateUrl('pico_download_entry_short', ['uniqId' => $entryUniqId, $entry->getSafeFilename()]),
                            ]);

                            return $this->redirectToRoute($redirRoute, $routeParams);
                        }

                        $this->addFlash('error', 'File Upload failed :/');
                        $routeParams = ['upload_failure' => 1];
                        return $this->redirectToRoute($redirRoute, $routeParams);
                    }
                }
                $resp->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $twigCtx = [
                'form' => $uploadForm->createView(),
                'showSubmitBtn' => true,
                'showProgressBar' => false,
                'pageTitle' => $isAdmin || $guestLink !== null ? 'Upload' : null,
            ];
        }

        if ($inline_form && $isSubrequest) {
            $twigCtx['pageTitle'] =  $isAdmin || $guestLink !== null ? 'Upload' : null;
            return $this->render('upload/_partials/_form_upload_file.html.twig', $twigCtx, $resp);
        }

        return $this->render('upload/upload_file.html.twig', $twigCtx, $resp);
    }


    #[Route('/uploaded/{uniqId}', name: 'pico_uploaded_with_success', methods: ['GET'])]
    public function successfulUpload(EntryRepository $entriesRepo, ?Ulid $uniqId, ?Ulid $guestLink = null): Response
    {
        $uploadedFile = $entriesRepo->findOneBy(['uniqLinkId' => $uniqId]);
        $foundGuestLink = $this->guestLinksRepo->findOneBy(['uniqLinkId' => $guestLink]);

        if ($uploadedFile === null) {
            throw $this->createNotFoundException('File not found!');
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        return $this->render('upload/successful_upload.html.twig', [
            'entryUniqId' => $uniqId->toBase58(),
            'entryFilename' => $uploadedFile->getSafeFilename(),
            'showEditBtn' => $isAdmin,
            'showUploadAnotherBtn' => $isAdmin || $foundGuestLink !== null,
        ]);
    }

    private function updateCurrentUploadsCounter(GuestLink $guestLink): void
    {
        // TODO: Maybe better with onKernelFinishRequest/onKernelTerminate
        $totalUploads = $this->entriesRepo->count(['guestLink' =>$guestLink]);
        $totalUploads++;
        $guestLink->setCurrentUploads($totalUploads);
    }
}
