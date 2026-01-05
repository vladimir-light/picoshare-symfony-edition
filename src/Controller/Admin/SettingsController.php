<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'pico_admin_settings')]
    public function index(): Response
    {
        // TODO
        throw new \BadMethodCallException('TBD...');
        return $this->render('admin/settings/index.html.twig', [
            'controller_name' => 'SettingsController',
        ]);
    }
}
