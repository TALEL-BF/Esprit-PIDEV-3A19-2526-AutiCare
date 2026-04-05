<?php

namespace App\Repository;

use App\Entity\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    /**
     * Crée un QueryBuilder avec tous les filtres
     */
    public function createFilteredQueryBuilder(
        ?string $searchTerm = null,
        ?string $type = null,
        ?string $sortBy = 'montant_desc'
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s');

        // 1. RECHERCHE INTELLIGENTE (texte + numérique)
        if (!empty($searchTerm)) {
            $searchTerm = trim($searchTerm);
            
            // RECHERCHE TEXTUELLE
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('s.nom', ':search'),
                $qb->expr()->like('s.description', ':search'),
                $qb->expr()->like('s.email', ':search'),
                $qb->expr()->like('s.telephone', ':search'),
                $qb->expr()->like('s.TypeSponsor', ':search')
            ))->setParameter('search', '%' . $searchTerm . '%');

            // RECHERCHE NUMÉRIQUE (ID, montant)
            if (is_numeric($searchTerm)) {
                $numericTerm = (int)$searchTerm;
                
                // Recherche par ID exact
                $qb->orWhere('s.idSponsor = :numericId')
                   ->setParameter('numericId', $numericTerm);
                
                // Recherche par montant exact
                $qb->orWhere('s.montant = :montant')
                   ->setParameter('montant', $numericTerm);
                
                // Recherche par montant approximatif
                if ($numericTerm <= 10000) {
                    $qb->orWhere($qb->expr()->like('s.montant', ':montantLike'))
                       ->setParameter('montantLike', '%' . $numericTerm . '%');
                }
            }
        }

        // 2. FILTRE TYPE
        if (!empty($type) && $type !== 'tous') {
            $qb->andWhere('s.TypeSponsor = :type')
               ->setParameter('type', $type);
        }

        // 3. TRI
        switch ($sortBy) {
            case 'nom_asc':
                $qb->orderBy('s.nom', 'ASC');
                break;
            case 'nom_desc':
                $qb->orderBy('s.nom', 'DESC');
                break;
            case 'montant_asc':
                $qb->orderBy('s.montant', 'ASC');
                break;
            case 'type_asc':
                $qb->orderBy('s.TypeSponsor', 'ASC');
                break;
            case 'type_desc':
                $qb->orderBy('s.TypeSponsor', 'DESC');
                break;
            case 'montant_desc':
            default:
                $qb->orderBy('s.montant', 'DESC');
                break;
        }

        return $qb;
    }

    /**
     * Recherche intelligente avec tous les filtres
     */
    public function searchWithFilters(
        ?string $searchTerm = null,
        ?string $type = null,
        ?string $sortBy = 'montant_desc'
    ): array {
        return $this->createFilteredQueryBuilder($searchTerm, $type, $sortBy)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Récupère le nombre total de sponsors avec filtres
     */
    public function countWithFilters(
        ?string $searchTerm = null,
        ?string $type = null
    ): int {
        $qb = $this->createFilteredQueryBuilder($searchTerm, $type, 'montant_desc');
        
        $qb->select('COUNT(s.idSponsor)');
        $qb->resetDQLPart('orderBy');
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}