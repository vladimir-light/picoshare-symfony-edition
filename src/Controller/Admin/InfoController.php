<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InfoController extends AbstractController
{
    #[Route('/information', name: 'pico_admin_info')]
    public function index(): Response
    {
        // TODO
        throw new \BadMethodCallException('TBD...');
        return $this->render('admin/info/index.html.twig', [
            'controller_name' => 'InfoController',
        ]);
    }
}
