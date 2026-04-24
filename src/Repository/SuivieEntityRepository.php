<?php

namespace App\Repository;

use App\Entity\SuivieEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuivieEntity>
 */
class SuivieEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuivieEntity::class);
    }

    /**
     * Eager-load the therapie relation to avoid N+1 queries
     * and prevent lazy-loading errors in Twig.
     */
    public function findAllWithTherapie(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.therapie', 't')
            ->addSelect('t')
            ->orderBy('s.dateSuivie', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
