<?php

namespace App\Repository;

use App\Entity\ClinicalNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClinicalNote>
 */
class ClinicalNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClinicalNote::class);
    }

    /**
     * @return ClinicalNote[]
     */
    public function findRecentByEnfant(int $enfantId, int $limit = 10): array
    {
        return $this->createQueryBuilder('cn')
            ->andWhere('cn.enfantId = :enfantId')
            ->setParameter('enfantId', $enfantId)
            ->orderBy('cn.sessionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
