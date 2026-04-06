<?php

namespace App\Repository;

use App\Entity\NiveauJeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NiveauJeu>
 */
class NiveauJeuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NiveauJeu::class);
    }

    /**
     * @return NiveauJeu[]
     */
    public function searchAndFilter(?string $search, ?string $niveau, ?string $tri): array
    {
        $qb = $this->createQueryBuilder('n');

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('n.libelle LIKE :search OR n.description LIKE :search')
               ->setParameter('search', '%' . trim($search) . '%');
        }

        if ($niveau !== null && trim($niveau) !== '') {
            $qb->andWhere('UPPER(n.libelle) = :niveau')
               ->setParameter('niveau', strtoupper(trim($niveau)));
        }

        switch ($tri) {
            case 'min_asc':
                $qb->orderBy('n.minMoyenne', 'ASC');
                break;
            case 'min_desc':
                $qb->orderBy('n.minMoyenne', 'DESC');
                break;
            case 'max_asc':
                $qb->orderBy('n.maxMoyenne', 'ASC');
                break;
            case 'max_desc':
                $qb->orderBy('n.maxMoyenne', 'DESC');
                break;
            case 'id_asc':
                $qb->orderBy('n.id', 'ASC');
                break;
            case 'id_desc':
            default:
                $qb->orderBy('n.id', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneByMoyenneRange(float $moyenne): ?NiveauJeu
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.minMoyenne <= :moyenne')
            ->andWhere('n.maxMoyenne >= :moyenne')
            ->setParameter('moyenne', $moyenne)
            ->getQuery()
            ->getOneOrNullResult();
    }
}