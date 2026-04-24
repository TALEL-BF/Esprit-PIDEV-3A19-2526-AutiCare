<?php

namespace App\Repository;

use App\Entity\SuiviTotal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuiviTotal>
 */
class SuiviTotalRepository extends ServiceEntityRepository
{
    /**
     * Retourne le dernier SuiviTotal pour un enfant donné
     */
    public function findLastByEnfantId(int $enfantId): ?\App\Entity\SuiviTotal
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.enfantId = :enfantId')
            ->setParameter('enfantId', $enfantId)
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuiviTotal::class);
    }

    //    /**
    //     * @return SuiviTotal[] Returns an array of SuiviTotal objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SuiviTotal
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
