<?php

namespace App\Controller\Admin;

use App\Repository\EntryRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InfoController extends AbstractController
{
    #[Route('/information', name: 'pico_admin_info')]
    public function index(EntryRepository $entriesRepo, Connection $defaultConnection): Response
    {
        $spaseUsageData = $this->getSpaceUsage($entriesRepo, $defaultConnection);
        $twigCtx = [
            'pageTitle' => 'System Information',
            'currentUsage' => $spaseUsageData,
        ];
        return $this->render('admin/info/index.html.twig', $twigCtx);
    }

    /**
     * @param EntryRepository $entriesRepo
     * @param Connection $dbConn
     * @return array{totalDbFilesize: false|int, totalFiles: int}
     */
    private function getSpaceUsage(EntryRepository $entriesRepo, Connection $dbConn): array
    {
        $sqliteDbFilesize = false;
        $totalFilesUsageInBytes = $entriesRepo->getEntriesSpaceUsage();
        if( $dbConn->getDriver()->getDatabasePlatform() instanceof SqlitePlatform)
        {
            $sqliteDbFilesize = filesize($dbConn->getParams()['path']);
        }

        return [
            'totalDbFilesize' => $sqliteDbFilesize,
            'totalFiles' => $totalFilesUsageInBytes,
        ];

    }
}
