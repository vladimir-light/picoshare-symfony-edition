<?php

namespace App\Repository;

use App\Entity\Entry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<Entry>
 */
class EntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entry::class);
    }

    //    /**
    //     * @return Entry[] Returns an array of Entry objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Entry
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function doDeleteEntryAndAllRelatedData(Entry|int $entryOrId, bool $doFlush = false): void
    {
        $internalEntryId = $entryOrId instanceof Entry ? $entryOrId->getId() : $entryOrId;
        // first, delete downloads-history
        // then, delete all chunks
        // and then delete the entry (file)


        //language=DQL
        $deletedDownloads = $this->getEntityManager()->createQuery(
            'DELETE FROM App\Entity\Download d WHERE IDENTITY(d.entry) = :entryId'
        )->execute([
            'entryId' => $internalEntryId
        ]);

        //language=DQL
        $deletedChunks = $this->getEntityManager()->createQuery(
            'DELETE FROM App\Entity\EntryChunk ec WHERE IDENTITY(ec.entry) = :entryId'
        )->execute([
            'entryId' => $internalEntryId
        ]);

        $this->getEntityManager()->remove($entryOrId);
        $doFlush and $this->getEntityManager()->flush();
    }

    public function getEntriesSpaceUsage(): int
    {
        // INFO: I'm switching back from raw-sql to simple queryBuilder without join to `entry_chunks` since total filesize is stored in Entry::size
        $query = $this->createQueryBuilder('e')->select('SUM(e.size)')->getQuery();

        return (int)$query->getSingleScalarResult();
    }

    /**
     * @param \DateTimeImmutable $now
     * @return list<Entry>|list<int>
     */
    public function getAllExpiredEntries(\DateTimeImmutable $now, bool $asFlatIdsList = false): array
    {
        // INFO: I know! I could just use CURRENT_DATE or CURRENT_TIMESTAMP instead of providing a DateTime as a parameter, but these are too specific to SQLite.
        //       Also, it doesn't give me an ability to "time travel" if I need it.
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.expiresAt IS NOT NULL')
            ->andWhere('e.expiresAt <= :dt_now')
            ->setParameter('dt_now', $now);

        if ($asFlatIdsList) {
            $qb->select('e.id AS entry_id');
            $data = $qb->getQuery()->getArrayResult();
            return array_column($data, 'entry_id');
        }

        return $qb->getQuery()->getResult();
    }


    /**
     * @param Ulid $uniqId
     * @return array{0: ?Ulid, 1: ?string, 2: ?string}|false
     */
    public function getEntrysEssentialMetadata(Ulid $uniqId): array|false
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e.uniqLinkId AS uniqId')
            ->addSelect('e.safeFilename AS safeFilename')
            ->addSelect('e.filename AS filename')
            ->andWhere('e.uniqLinkId = :uniqId')->setParameter('uniqId', $uniqId->toBinary());

        $found = $qb->getQuery()->getArrayResult();
        if (isset($found[0]['uniqId'])) {
            $found = reset($found);
            return [$found['uniqId'], $found['safeFilename'], $found['filename']];
        }

        return false;
    }
}
