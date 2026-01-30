<?php

namespace App\Service;

use App\Entity\Entry;
use App\Entity\EntryChunk;
use App\Entity\GuestLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Ulid;

final class UploadsHandler
{
    private const CHUNK_SIZE_1MB = 1024 * 1024;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface       $slugger,
    )
    {
    }

    public function processUploadedFile(UploadedFile $uploadedFile, ?GuestLink $guestLink = null, ?string $adminsNote = null, ?string $expirationString = null): ?Entry
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);

        $success = false;
        try {
            $this->em->beginTransaction();

            $entry = (new Entry())
                ->setUniqLinkId(new Ulid())
                ->setNote($adminsNote)
                ->setSize($uploadedFile->getSize())
                ->setFilename($originalFilename . '.' . $uploadedFile->getClientOriginalExtension())
                ->setSafeFilename($safeFilename . '.' . $uploadedFile->getClientOriginalExtension())
                ->setContentType($uploadedFile->getMimeType())
                ->setGuestLink($guestLink);
            $this->em->persist($entry);


            $ioHandle = \fopen($uploadedFile->getPathname(), 'rb');

            $chunkIndex = 0;
            while (false === feof($ioHandle)) {
                $chunkData = fread($ioHandle, self::CHUNK_SIZE_1MB);
                if (!$chunkData) break;

                $chunk = (new EntryChunk())
                    ->setDataChunk($chunkData)
                    ->setDataChunkIndex($chunkIndex)
                    ->setDataChunkSize(mb_strlen($chunkData, '8bit'))
                    ->setEntry($entry);
                $chunkIndex++;
                $this->em->persist($chunk);
            }

            // null -> never or not available
            if ($expirationString !== null) {
                $refNow = new \DateTimeImmutable('today');
                $expirationModifier = str_replace('-', ' ', $expirationString);
                $newExpirationDateTime = $refNow->modify($expirationModifier);
                $entry->setExpiresAt($newExpirationDateTime);
            }

            $this->em->flush();
            $this->em->commit();
            $success = true;
        } catch (\Throwable $err) {
            $this->em->rollback();
            throw $err;
        } finally {
            fclose($ioHandle);
            if ($success && $guestLink !== null) {
                $this->updateCurrentUploadsCounter($guestLink);
            }
        }

        return $success ? $entry : null;
    }

    private function updateCurrentUploadsCounter(GuestLink $guestLink): void
    {
        // TODO: Maybe better with onKernelFinishRequest/onKernelTerminate
        $totalUploads = $this->em->getRepository(Entry::class)->count(['guestLink' => $guestLink]);
        $totalUploads++;
        $guestLink->setCurrentUploads($totalUploads);
    }
}
