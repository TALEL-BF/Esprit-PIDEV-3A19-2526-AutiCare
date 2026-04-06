<?php
// src/Repository/CoursRepository.php

namespace App\Repository;

use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cours>
 */
class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }
    
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.typeCours = :type')
            ->setParameter('type', $type)
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function findByNiveau(string $niveau): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.niveau = :niveau')
            ->setParameter('niveau', $niveau)
            ->orderBy('c.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}