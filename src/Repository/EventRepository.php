<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Crée un QueryBuilder avec tous les filtres (pour pagination)
     */
    public function createFilteredQueryBuilder(
        ?string $searchTerm = null,
        ?string $type = null,
        ?string $status = null,
        ?string $period = null,
        ?string $sortBy = 'date_desc'
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('e');

        // 1. RECHERCHE INTELLIGENTE (texte + numérique)
        if (!empty($searchTerm)) {
            $searchTerm = trim($searchTerm);
            
            // RECHERCHE TEXTUELLE (tous les champs texte)
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('e.titre', ':search'),
                $qb->expr()->like('e.description', ':search'),
                $qb->expr()->like('e.typeEvent', ':search'),
                $qb->expr()->like('e.lieu', ':search'),
                $qb->expr()->like('e.status', ':search')
            ))->setParameter('search', '%' . $searchTerm . '%');

            // RECHERCHE NUMÉRIQUE (ID, capacité)
            if (is_numeric($searchTerm)) {
                $numericTerm = (int)$searchTerm;
                
                // Recherche par ID exact
                $qb->orWhere('e.idEvent = :numericId')
                   ->setParameter('numericId', $numericTerm);
                
                // Recherche par capacité exacte
                $qb->orWhere('e.maxParticipant = :capacity')
                   ->setParameter('capacity', $numericTerm);
                
                // Recherche par capacité approximative (si le nombre est petit)
                if ($numericTerm <= 100) {
                    $qb->orWhere($qb->expr()->like('e.maxParticipant', ':capacityLike'))
                       ->setParameter('capacityLike', '%' . $numericTerm . '%');
                }
            }
            
            // RECHERCHE PAR TÉLÉPHONE (si tu as un champ phone)
            // if (preg_match('/^[0-9]{10}$/', $searchTerm)) {
            //     $qb->orWhere('e.telephone = :tel')
            //        ->setParameter('tel', $searchTerm);
            // }
        }

        // 2. FILTRE TYPE
        if (!empty($type) && $type !== 'tous') {
            $qb->andWhere('e.typeEvent = :type')
               ->setParameter('type', $type);
        }

        // 3. FILTRE STATUT
        if (!empty($status) && $status !== 'tous') {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $status);
        }

        // 4. FILTRE PÉRIODE
        $today = new \DateTime();
        if (!empty($period) && $period !== 'toutes') {
            switch ($period) {
                case 'upcoming':
                    $qb->andWhere('e.dateDebut >= :today')
                       ->setParameter('today', $today);
                    break;
                case 'past':
                    $qb->andWhere('e.dateDebut < :today')
                       ->setParameter('today', $today);
                    break;
                case 'this_month':
                    $start = new \DateTime('first day of this month');
                    $end = new \DateTime('last day of this month');
                    $qb->andWhere('e.dateDebut BETWEEN :start AND :end')
                       ->setParameter('start', $start)
                       ->setParameter('end', $end);
                    break;
                case 'next_month':
                    $start = new \DateTime('first day of next month');
                    $end = new \DateTime('last day of next month');
                    $qb->andWhere('e.dateDebut BETWEEN :start AND :end')
                       ->setParameter('start', $start)
                       ->setParameter('end', $end);
                    break;
            }
        }

        // 5. TRI
        switch ($sortBy) {
            case 'date_asc':
                $qb->orderBy('e.dateDebut', 'ASC');
                break;
            case 'title_asc':
                $qb->orderBy('e.titre', 'ASC');
                break;
            case 'title_desc':
                $qb->orderBy('e.titre', 'DESC');
                break;
            case 'capacity_desc':
                $qb->orderBy('e.maxParticipant', 'DESC');
                break;
            case 'capacity_asc':
                $qb->orderBy('e.maxParticipant', 'ASC');
                break;
            case 'type_asc':
                $qb->orderBy('e.typeEvent', 'ASC');
                break;
            case 'lieu_asc':
                $qb->orderBy('e.lieu', 'ASC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('e.dateDebut', 'DESC');
                break;
        }

        return $qb;
    }

    /**
     * Recherche intelligente avec tous les filtres (version array - pour compatibilité)
     */
    public function searchWithFilters(
        ?string $searchTerm = null,
        ?string $type = null,
        ?string $status = null,
        ?string $period = null,
        ?string $sortBy = 'date_desc'
    ): array {
        return $this->createFilteredQueryBuilder($searchTerm, $type, $status, $period, $sortBy)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Récupère le nombre total d'événements avec filtres
     */
    public function countWithFilters(
        ?string $searchTerm = null,
        ?string $type = null,
        ?string $status = null,
        ?string $period = null
    ): int {
        $qb = $this->createFilteredQueryBuilder($searchTerm, $type, $status, $period, 'date_desc');
        
        // Compter sans l'ORDER BY pour les performances
        $qb->select('COUNT(e.idEvent)');
        $qb->resetDQLPart('orderBy');
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}