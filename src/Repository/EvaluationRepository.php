<?php

namespace App\Repository;

use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evaluation>
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    /**
     * @return Evaluation[] Returns an array of Evaluation objects for a specific course
     */
    public function findByCoursId(int $coursId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.idCours = :coursId')
            ->setParameter('coursId', $coursId)
            ->orderBy('e.idEval', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}