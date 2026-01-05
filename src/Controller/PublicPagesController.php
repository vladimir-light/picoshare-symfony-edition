<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicPagesController extends AbstractController
{
    #[Route('/', name: 'pico_home')]
    public function home(): Response
    {
        return $this->render('public/home.html.twig', [
            'pageTitle' => 'Welcome!'
        ]);
    }

    #[Route('/about', name: 'pico_about')]
    public function about(): Response
    {
        return $this->render('public/about.html.twig', [
           'pageTitle' => 'About'
        ]);
    }

    public function singleBlockSectionAbout(RequestStack $requestStack): Response
    {
        $resp = $this->renderBlock('public/about.html.twig', 'block_about', []);
        $resp->setPublic();
        $resp->setMaxAge(86400);

        return $resp;
    }
}
