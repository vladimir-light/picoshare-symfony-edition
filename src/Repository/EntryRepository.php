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
    public function doDeleteEntryAndAllDataChunks(?Entry $file, ?bool $doFlush = false): void
    {
        $internalEntryId = $file->getId();
        // first, delete all chunks
        // then delete entry (file) itself


        $this->getEntityManager()->beginTransaction();
        //language=DQL
        $deleted = $this->getEntityManager()->createQuery(
            'DELETE FROM App\Entity\EntryChunk ec WHERE IDENTITY(ec.entry) = :entryId'
        )->execute([
            'entryId' => $internalEntryId
        ]);

        if( $deleted )
        {
            $this->getEntityManager()->remove($file);
        }

        $doFlush and $this->getEntityManager()->flush();
        $this->getEntityManager()->commit();
    }
}
