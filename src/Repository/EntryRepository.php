<?php

namespace App\Repository;

use App\Entity\Entry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
    public function doDeleteEntryAndAllRelatedData(?Entry $file, ?bool $doFlush = false): void
    {
        $internalEntryId = $file->getId();
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

        $this->getEntityManager()->remove($file);
        $doFlush and $this->getEntityManager()->flush();
    }

    public function getEntriesSpaceUsage(): int
    {
        // INFO: I'm switching back from raw-sql to simple queryBuilder without join to `entry_chunks` since total filesize is stored in Entry::size
        $query = $this->createQueryBuilder('e')->select('SUM(e.size)')->getQuery();

        return (int)$query->getSingleScalarResult();
    }
}
