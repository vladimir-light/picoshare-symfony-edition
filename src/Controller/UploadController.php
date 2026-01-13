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

        if ($foundGuestLink === null) {
            throw $this->createNotFoundException('Guest Link is not found!');
        }
        // check if guest link still usable (not expired, not disabled and upload-limit not reached yet)
        $linkIsNoLongerUsable = $foundGuestLink->isExpired(new \DateTimeImmutable('now')) || $foundGuestLink->isMaxAllowedUploadsReached() || $foundGuestLink->isDisabled();

        if ($linkIsNoLongerUsable) {
            /** @see self::showGuestLinkIsNoLongerActive() */
            return $this->forward(__CLASS__ . '::showGuestLinkIsNoLongerActive', [
                'guestLink' => $foundGuestLink,
            ]);
        }

        return $this->upload($requestStack, $slugger, $foundGuestLink, $inline_form);
    }


    /*
     * FIXME: This method already needs refactoring
     */
    #[Route('/upload', name: 'pico_upload_file', methods: ['POST', 'PUT', 'GET'])]
    public function upload(RequestStack $requestStack, SluggerInterface $slugger, ?GuestLink $guestLink = null, bool $inline_form = false): Response
    {
        $request = $requestStack->getCurrentRequest();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isSubrequest = $requestStack->getParentRequest() !== null;

        // not authenticated and not a guest-link? show 404!
        if ($guestLink === null && $isAdmin === false) {
            //TODO: 403 if ajax/subrequest?
            throw $this->createNotFoundException('Page not found!');
        }

        $uploadPossible = true; // we assume - upload is always possible
        $maxUploadFilesize = null; // we assume - there's no upload limit
        $redirRoute = 'pico_upload_file';
        $routeParams = [];
        if ($guestLink !== null) {
            $routeParams['guestLinkUniqId'] = $guestLink->getUniqLinkId()->toBase58();
            $redirRoute = 'pico_upload_file_with_guest_link';
        }


        $resp = new Response(null);
        $uploadForm = null;
        $twigCtx = [
            'showSubmitBtn' => true,
            'showProgressBar' => false,
            'pageTitle' => $isAdmin || $guestLink !== null ? 'Upload' : null,
            'guestLinkUniqId' => $guestLink?->getUniqLinkId()->toBase58(),
        ];

        // trying to upload via guest link, but guest link is no longer usable?
        if ($guestLink !== null && $guestLink->isUnlimitedFileSize() === false) {
            $maxUploadFilesize = $guestLink->getMaxFileSizeInMegaBytes(); // as integer
        }

        if( $uploadPossible )
        {
            // TODO: Make guest-link object as nullable form-option and perform all check in the form-class!
            $uploadForm = $this->createForm(UploadFormType::class, null, [
                'is_guest_link' => $guestLink !== null,
                'preselected_auto_expire_value' => $guestLink?->getFileExpiration(),
                'custom_max_upload_filesize_in_mb' => $maxUploadFilesize,
                'action' => $guestLink === null ? $this->generateUrl('pico_upload_file') : $this->generateUrl('pico_upload_file_with_guest_link', ['guestLinkUniqId' => $guestLink->getUniqLinkId()->toBase58()]),
                'method' => Request::METHOD_POST,
            ]);

            $twigCtx['form'] = $uploadForm->createView();
        }

        // ...Uploading a file
        if ($request->isMethod(Request::METHOD_POST)) {
            $uploadForm->handleRequest($request);

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
                            $this->addFlash('success', ' Upload complete!');

                            /** @see self::showDownloadLinks() */
                            return $this->forward(__CLASS__ . '::showDownloadLinks', [
                                'entryUniqId' => $entry->getUniqLinkId(),
                                'safeFilename' => $entry->getSafeFilename(),
                            ]);
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

    public function showGuestLinkIsNoLongerActive(GuestLink $guestLink): Response
    {
        return $this->render('upload/guest_link_not_usable.html.twig', [
            'pageTitle' => 'Guest Link Inactive',
        ]);
    }
    public function showDownloadLinks(Ulid $entryUniqId, string $safeFilename): Response
    {
        return $this->render('upload/successful_upload.html.twig', [
            'entryUniqId' => $entryUniqId->toBase58(),
            'entryFilename' => $safeFilename,
            'showEditBtn' => false,
            'showUploadAnotherBtn' => true,
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
