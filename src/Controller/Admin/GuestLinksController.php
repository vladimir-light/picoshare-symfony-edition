<?php

namespace App\Controller\Admin;

use App\Entity\GuestLink;
use App\Form\GuestLinkType;
use App\Repository\GuestLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;

final class GuestLinksController extends AbstractController
{

    public function __construct
    (
        private EntityManagerInterface $em,
        private GuestLinkRepository    $guestLinksRepo,
        private LoggerInterface        $logger,
    )
    {
    }

    #[Route('/guest-links', name: 'pico_admin_guest_links')]
    public function index(): Response
    {
        return $this->render('admin/guest_links/index.html.twig', [
            'pageTitle' => 'Guest Links',
            'pageDesc' => 'Manage guest links',
            'allowAddNewEntry' => true,
            'newEntryViaModal' => true,
        ]);
    }

    public function guestLinksList(RequestStack $requestStack): Response
    {
        $resp = new Response(null);

        $foundGuestLinks = $this->guestLinksRepo->findAll();

        return $this->render('admin/guest_links/_crud_table_guest_links.html.twig', [
            'paginatedResults' => false === empty($foundGuestLinks) ? $foundGuestLinks : false,
            'showPagination' => false,
        ], $resp);
    }

    #[Route('/guest-links/new', name: 'pico_admin_guest_link_new', methods: [Request::METHOD_POST, Request::METHOD_GET])]
    #[Route('/guest-links/{uniqId}/edit', name: 'pico_admin_guest_link_edit', methods: [Request::METHOD_POST, Request::METHOD_GET])]
    public function editOrCreateGuestLink(Request $request, ?Ulid $uniqId = null): Response
    {
        $isNew = false;
        if ($uniqId === null && $request->attributes->get('_route') === 'pico_admin_guest_link_new') {
            $isNew = true;
        }

        $guestLink = new GuestLink(null);
        if (!$isNew) {
            $guestLink = $this->getGuestLinkOrPanicWith404($uniqId);
        }

        $form = $this->createForm(GuestLinkType::class, $guestLink, [
            'method' => Request::METHOD_POST,
            'action' => $isNew ? $this->generateUrl('pico_admin_guest_link_new') : $this->generateUrl('pico_admin_guest_link_edit', ['uniqId' => $uniqId->toBase58()]),
        ]);

        $resp = new Response();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $this->em->beginTransaction();
                $success = false;
                try {
                    if ($isNew) {
                        $guestLink->setUniqLinkId(new Ulid());
                        $this->em->persist($guestLink);
                        $uniqId = $guestLink->getUniqLinkId();
                    }

                    $this->em->flush();
                    $this->em->commit();
                    $success = true;
                } catch (\Throwable $err) {
                    $rollback = false;
                    if ($this->em->getConnection()->isTransactionActive()) {
                        $this->em->rollback();
                        $rollback = true;
                    }
                    $this->logger->error('Guest-Link `{guest_link_id}`: {actionType} action failed!', [
                        'exception' => $err,
                        'guest_link_id' => $uniqId?->toBase58(),
                        'actionType' => $isNew ? 'CREATE NEW' : 'EDIT',
                        'db_rollback' => $rollback ? 'yes' : 'no',
                    ]);
                    $this->addFlash('error', 'Something went wrong :/ Error: ' . $err->getMessage());
                }

                if ($success) {
                    $flashMsg = 'Guest-Link created';
                    if (!$isNew) {
                        $flashMsg = sprintf('Guest-Link (%s) updated', $uniqId->toBase58());
                    }


                    $this->addFlash('success', $flashMsg);
                    return $this->redirectToRoute('pico_admin_guest_links');
                }
            }

            $resp->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        $twigCtx = [
            'entryUniqId' => $guestLink?->getUniqLinkId(),
            'pageTitle' => $isNew ? 'Create Guest Link' : 'Edit Guest Link',
            'form' => $form?->createView(),
            'guestLink' => $guestLink,
            'isNew' => $isNew,
        ];

        return $this->render('admin/guest_links/guest_link_edit.html.twig', $twigCtx, $resp);
    }

    #[Route('/guest-links/{uniqId}/confirm-delete', name: 'pico_admin_guest_link_confirm_delete', methods: [Request::METHOD_GET, Request::METHOD_DELETE])]
    public function confirmDeleteGuestLink(Request $request, Ulid $uniqId): RedirectResponse|Response
    {
        $foundGuestLink = $this->getGuestLinkOrPanicWith404($uniqId);
        $deleteForm = $this->createDeleteForm($foundGuestLink->getUniqLinkId());
        $isFromEditAction = $request->query->get('_ref') === 'edit';

        if ($request->isMethod(Request::METHOD_DELETE)) {
            $deleteForm->handleRequest($request);
            if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
                $deleteAllFiles = $deleteForm?->get('deleteAllFiles')->getData();

                $this->em->beginTransaction();
                $allGood = false;
                try {
                    $this->em->remove($foundGuestLink);
                    if ($deleteAllFiles) {
                        $this->deleteAllFilesOfGuestLink($foundGuestLink);
                    }
                    $this->em->flush();
                    $this->em->commit();
                    $allGood = true;
                } catch (\Throwable $err) {
                    $rollback = false;
                    if ($this->em->getConnection()->isTransactionActive()) {
                        $this->em->rollback();
                        $rollback = true;
                    }
                    $this->logger->error('Guest-Link `{guest_link_id}`: DELETE action failed!', [
                        'exception' => $err,
                        'guest_link_id' => $uniqId?->toBase58(),
                        'db_rollback' => $rollback ? 'yes' : 'no',
                    ]);
                }

                if ($allGood) {
                    $msg = 'Guest-link was successfully deleted!';
                    if ($deleteAllFiles) {
                        $msg = 'Guest-link (and all associated files) were successfully deleted!';
                    }
                    $this->addFlash('success', $msg);
                    return $this->redirectToRoute('pico_admin_guest_links');
                }

                $this->addFlash('error', 'Something went wrong :/');
                return $this->redirectToRoute('pico_admin_guest_link_confirm_delete', ['uniqId' => $uniqId->toBase58()]);
            }
        }

        $twigCtx = [
            'deleteForm' => $deleteForm?->createView(),
            'guestLink' => $foundGuestLink,
            'pageTitle' => 'Delete Guest-Link',
            'isFromEdit' => $isFromEditAction,
        ];

        return $this->render('admin/guest_links/guest_link_confirm_delete.html.twig', $twigCtx);
    }

    #[Route(path: '/guest-links/{uniqId}/enable', name: 'pico_admin_guest_link_enable', methods: [Request::METHOD_PUT, Request::METHOD_GET])]
    public function enableGuestLink(Ulid $uniqId): RedirectResponse
    {
        $foundGuestLink = $this->getGuestLinkOrPanicWith404($uniqId);
        if($foundGuestLink->isDisabled() === false)
        {
            $this->addFlash('warning', sprintf('Guest-Link (%s) is already enabled!', $uniqId->toBase58()));
            return $this->redirectToRoute('pico_admin_guest_links');
        }
        return $this->toggleGuestLinkState($foundGuestLink, 'enable');
    }

    #[Route(path: '/guest-links/{uniqId}/disable', name: 'pico_admin_guest_link_disable', methods: [Request::METHOD_PUT, Request::METHOD_GET])]
    public function disabledGuestLink(Ulid $uniqId): RedirectResponse
    {
        $foundGuestLink = $this->getGuestLinkOrPanicWith404($uniqId);
        if($foundGuestLink->isDisabled())
        {
            $this->addFlash('warning', sprintf('Guest-Link (%s) is already disabled!', $uniqId->toBase58()));
            return $this->redirectToRoute('pico_admin_guest_links');
        }
        return $this->toggleGuestLinkState($foundGuestLink, 'disable');
    }

    private function toggleGuestLinkState(GuestLink $guestLink, string $action): RedirectResponse
    {
        $guestLink->setDisabled( $action === 'disable' );
        $this->em->flush();

        $this->addFlash('success', sprintf('Guest-Link (%s) successfully %s', $guestLink->getUniqLinkId()->toBase58(), $action === 'disable' ? 'disabled' : 'enabled' ));
        return $this->redirectToRoute('pico_admin_guest_links');
    }

    private function getGuestLinkOrPanicWith404(Ulid $uniqId, string $notFoundMessage = 'GuestLink not found!'): ?GuestLink
    {
        $guestLink = $this->guestLinksRepo->findOneBy(['uniqLinkId' => $uniqId]);
        if ($guestLink === null) {
            throw $this->createNotFoundException($notFoundMessage);
        }
        return $guestLink;
    }

    private function createDeleteForm(Ulid $uniqId): FormInterface
    {
        $form = $this->createFormBuilder(null, [
            'method' => Request::METHOD_DELETE,
            'action' => $this->generateUrl('pico_admin_guest_link_confirm_delete', [
                'uniqId' => $uniqId->toBase58(),
                'confirmed' => '✓'
            ]),
        ]);

        $form->add('entryId', HiddenType::class, [
            'label' => false,
            'data' => $uniqId->toBase58(),
        ]);

        $form->add('deleteAllFiles', CheckboxType::class, [
            'label' => 'Also delete all files?',
            'mapped' => false,
            'data' => false,
            'required' => false,
        ]);

        $form->add('submit', SubmitType::class, [
            'label' => 'Delete',
        ]);

        return $form->getForm();
    }

    private function deleteAllFilesOfGuestLink(GuestLink $guestLink): void
    {
        // TODO: Improve! EntryChunks and Downloads should be also deleted.
        //        Find all distinct entries_id of this guest, then perform $entriesRepo->doDeleteEntryAndAllRelatedData($id)
        //language=DQL
        $this->em->createQuery('DELETE FROM App\Entity\Entry file WHERE IDENTITY(file.guestLink) = :givenGuestLink')->execute([
            'givenGuestLink' => $guestLink,
        ]);
    }
}
