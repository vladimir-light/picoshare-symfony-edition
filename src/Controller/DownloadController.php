<?php

namespace App\Controller;

use App\Entity\Download;
use App\Entity\Entry;
use App\Repository\EntryChunkRepository;
use App\Repository\EntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Ulid;

final class DownloadController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    )
    {}

    #[Route('/{uniqId}/{filename}', name: 'pico_download_entry_full', requirements: ['uniqId' => Requirement::UID_BASE58], methods: ['GET'])]
    #[Route('/{uniqId}', name: 'pico_download_entry_short', requirements: ['uniqId' => Requirement::UID_BASE58], methods: ['GET'])]
    public function download(Request $request, EntryRepository $filesRepo, EntryChunkRepository $chunksRepo, Ulid $uniqId, ?string $filename = null): Response
    {
        $entry = $filesRepo->findOneBy(['uniqLinkId' => $uniqId]);
        if ($entry === null) {
            throw $this->createNotFoundException('File not found!');
        }

        if ($entry->isEndless() === false) {
            $now = new \DateTimeImmutable('now');
            if ($entry->getExpiresAt()->getTimestamp() < $now->getTimestamp()) {
                throw $this->createNotFoundException('File not found!');
            }
        }

        $this->tryToCreateDownloadHistory($request, $entry, new \DateTimeImmutable('now'));

        $response = new StreamedResponse();
        $response->setCallback(function () use ($entry, $chunksRepo) {
            $totalChunks = $entry->getEntryChunks()->count();
            for ($idx = 0; $idx < $totalChunks; $idx++) {
                $chunk = $chunksRepo->findOneBy(['entry' => $entry, 'dataChunkIndex' => $idx]);
                $chunkContents = $chunk->getDataChunk();
                while (!feof($chunkContents)) {
                    echo fread($chunkContents, $chunk->getDataChunkSize());
                    flush();
                }
                fclose($chunkContents);
            }
            unset($chunk, $chunkContents);
        });

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $entry->getFilename()
        );

        $response->headers->set('Content-Type', $entry->getContentType());
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', $entry->getSize());

        return $response;
    }

    private function tryToCreateDownloadHistory(Request $request, Entry $file, ?\DateTimeInterface $downloadedAt = null): void
    {
        // TODO: Maybe better with onKernelFinishRequest/onKernelTerminate - if possible - since $request could be null
        $ua = $request->headers->get('user-agent');
        $ip = $request->server->get('REMOTE_ADDR');

        $downloadedAt instanceof \DateTimeInterface and $downloadedAt = new \DateTimeImmutable('now');

        $history = (new Download())
                    ->setEntry($file)
                    ->setDownloadedAt($downloadedAt)
                    ->setClientIp($ip)
                    ->setUserAgent($ua);

        try {
            $this->em->persist($history);
            $this->em->flush();
        }
        catch (\Throwable $er)
        {
            // it's not critical if it doesn't save downloads info
            // TODO: ...but maybe add logs!
        }
    }
}
