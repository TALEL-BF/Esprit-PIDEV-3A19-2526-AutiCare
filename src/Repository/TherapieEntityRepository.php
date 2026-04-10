<?php

namespace App\Repository;

use App\Entity\TherapieEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TherapieEntity>
 */
class TherapieEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TherapieEntity::class);
    }

    // src/Repository/TherapieEntityRepository.php

public function findByNiveaux(string $niveauxHumeur, string $niveauxStresse, string $niveauxAttention): array
{
    return $this->createQueryBuilder('t')
        ->where('t.niveauxHumeur LIKE :humeur')
        ->andWhere('t.niveauxStresse LIKE :stress')
        ->andWhere('t.niveauxAttention LIKE :attention')

        ->setParameter('humeur', '%' . $niveauxHumeur . '%')
        ->setParameter('stress', '%' . $niveauxStresse . '%')
        ->setParameter('attention', '%' . $niveauxAttention . '%')

        ->getQuery()
        ->getResult();
}


    //    /**
    //     * @return TherapieEntity[] Returns an array of TherapieEntity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TherapieEntity
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
