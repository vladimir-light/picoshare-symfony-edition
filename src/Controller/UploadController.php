<?php

namespace App\Controller;

use App\Entity\Entry;
use App\Entity\GuestLink;
use App\Form\UploadFormType;
use App\Repository\GuestLinkRepository;
use App\Service\UploadsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

final class UploadController extends AbstractController
{
    private const SESSIONKEY_PREV_UPLOADED_METADATA = 'pico/uploaded';

    public function __construct
    (
        private readonly GuestLinkRepository $guestLinksRepo,
        private readonly UploadsHandler      $uploadsHandler,
    )
    {
    }

    #[Route('/g/{guestLinkUniqId}', name: 'pico_upload_file_with_guest_link', methods: ['GET', 'POST', 'PUT'])]
    public function guestLinkUpload(RequestStack $requestStack, Ulid $guestLinkUniqId, bool $inline_form = false): Response
    {
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

        return $this->upload($requestStack, $inline_form, $foundGuestLink);
    }


    #[Route('/upload', name: 'pico_upload_file', methods: ['POST', 'PUT', 'GET'])]
    public function upload(RequestStack $requestStack, bool $inline_form = false, ?GuestLink $guestLink = null): Response
    {
        $request = $requestStack->getCurrentRequest();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isSubrequest = $requestStack->getParentRequest() !== null;

        // not authenticated and not a guest-link? show 404!
        if ($guestLink === null && $isAdmin === false) {
            //TODO: 403 if ajax/subrequest?
            throw $this->createNotFoundException('Page not found!');
        }


        $redirRoute = 'pico_upload_file';
        $routeParams = [];
        if ($guestLink !== null) {
            $routeParams['guestLinkUniqId'] = $guestLink->getUniqLinkId()->toBase58();
            $redirRoute = 'pico_upload_file_with_guest_link';
        }

        // check if we were redirected back after a successfull upload.
        // and if there is -> show download link only
        if ($request->isMethod(Request::METHOD_GET)
            && $request->getSession()->has(self::SESSIONKEY_PREV_UPLOADED_METADATA)
            && $request->headers->has('referer')
        ) {
            [$prevUploadedUniqId, $filename] = $request->getSession()->get(self::SESSIONKEY_PREV_UPLOADED_METADATA, []);
            $request->getSession()->remove(self::SESSIONKEY_PREV_UPLOADED_METADATA); // remove immediately
            if ($prevUploadedUniqId !== null) {
                /** @see self::showDownloadLinks() */
                return $this->forward(__CLASS__ . '::showDownloadLinks', [
                    'entryUniqId' => $prevUploadedUniqId,
                    'safeFilename' => $filename,
                    'showEditLink' => $guestLink === null && $this->isGranted('ROLE_ADMIN') // edit-button is visible ONLY for authenticated admin. If it's a guest-link upload -> never show edit-button
                ]);
            }
        }


        $resp = new Response(null);
        $uploadForm = null;
        $uploadPossible = true; // we assume -> upload is always possible
        $maxUploadFilesize = null; // we assume -> there's no upload limit
        //
        $twigCtx = [
            'showSubmitBtn' => true,
            'showProgressBar' => true,
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
                    $note = $uploadForm->has('note') ? $uploadForm->get('note')->getData() : null;
                    $autoExpireAt = $uploadForm->has('auto_expire') ? $uploadForm->get('auto_expire')->getData() : null;
                    $entry = $this->uploadsHandler->processUploadedFile($uploadForm->get('file')->getData(), $guestLink, $note, $autoExpireAt);
                    $success = $entry instanceof Entry;

                    if ($success) {
                        $this->addFlash('success', 'Upload complete!');
                        $request->getSession()->set(self::SESSIONKEY_PREV_UPLOADED_METADATA, [$entry->getUniqLinkId(), $entry->getSafeFilename()]);
                        return $this->redirectToRoute($redirRoute, $routeParams);
                    }

                    $this->addFlash('error', 'File Upload failed :/');
                    $routeParams = ['upload_failure' => 1];
                    return $this->redirectToRoute($redirRoute, $routeParams);
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
            $twigCtx['inline_form'] = true;
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
    public function showDownloadLinks(Ulid $entryUniqId, string $safeFilename, bool $showEditLink = false): Response
    {
        return $this->render('upload/successful_upload.html.twig', [
            'entryUniqId' => $entryUniqId->toBase58(),
            'entryFilename' => $safeFilename,
            'showEditBtn' => $showEditLink,
            'showUploadAnotherBtn' => true,
        ]);
    }
}
