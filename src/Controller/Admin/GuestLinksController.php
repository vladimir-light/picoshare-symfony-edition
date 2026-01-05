<?php

namespace App\Controller\Admin;

use App\Entity\GuestLink;
use App\Form\GuestLinkType;
use App\Repository\GuestLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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

//    #[Route('/guest-links/new', name: 'pico_admin_guest_links_new', methods: [Request::METHOD_POST, Request::METHOD_GET])]
//    public function createNew(Request $request, Ulid $uniqId)
//    {
//        $guestLink = new GuestLink();
//        $form = $this->createForm(GuestLinkType::class, $guestLink, [
//            'method' => Request::METHOD_POST,
//            'action' => $this->generateUrl('pico_admin_guest_links_new')
//        ]);
//
//        $resp = new Response();
//
//        if ($form->isSubmitted()) {
//            if ($form->isValid()) {
//                dd($guestLink);
//                $this->addFlash('success', 'New Guest-Link has been created');
//                return $this->redirectToRoute('pico_admin_guest_links');
//            }
//
//            $resp->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
//        }
//
//        $twigCtx = [
//            'form' => $form?->createView(),
//            'guestLink' => $guestLink,
//            'isNew' => $guestLink->getId() === null,
//        ];
//
//        return $this->render('admin/guest_links/guest_link_edit.html.twig', $twigCtx, $resp);
//    }

    #[Route('/guest-links/new', name: 'pico_admin_guest_link_new', methods: [Request::METHOD_POST, Request::METHOD_GET])]
    #[Route('/guest-links/{uniqId}/edit', name: 'pico_admin_guest_link_edit', methods: [Request::METHOD_POST, Request::METHOD_GET])]
    public function ediOrCreateGuestLink(Request $request, ?Ulid $uniqId = null): Response
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

    #[Route('/guest-links/{uniqId}/confirm-delete', name: 'pico_admin_guest_links_confirm_delete')]
    public function confirmDeleteGuestLink(Request $request, Ulid $uniqId)
    {
        // TODO:
        throw new \BadMethodCallException('TBD...');

        $foundGuestLink = $this->getGuestLinkOrPanicWith404($uniqId);
        $deleteForm = $this->createDeleteForm($uniqId);
    }

    private function getGuestLinkOrPanicWith404(Ulid $uniqId, string $notFoundMessage = 'GuestLink not found!'): ?GuestLink
    {
        $guestLink = $this->guestLinksRepo->findOneBy(['uniqLinkId' => $uniqId]);
        if ($guestLink === null) {
            throw $this->createNotFoundException($notFoundMessage);
        }
        return $guestLink;
    }

    private function createDeleteForm(Ulid $uniqId): \Symfony\Component\Form\FormInterface
    {
        $form = $this->createFormBuilder(null, [
            'method' => Request::METHOD_DELETE,
            'action' => $this->generateUrl('pico_admin_guest_links_confirm_delete', [
                'uniqId' => $uniqId->toBase58(),
                'confirmed' => '✓'
            ]),
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
