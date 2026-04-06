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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuiviTotal::class);
    }

    /**
     * @return SuiviTotal[]
     */
    public function searchAndFilter(
        ?string $search = null,
        ?string $niveau = null,
        ?string $tri = 'id_desc'
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.niveauJeu', 'n')
            ->addSelect('n');

        if ($search) {
            $qb->andWhere('(s.id = :searchId OR s.enfantId = :searchId OR s.remarque LIKE :search)')
               ->setParameter('search', '%' . $search . '%')
               ->setParameter('searchId', ctype_digit($search) ? (int) $search : 0);
        }

        if ($niveau !== null && trim($niveau) !== '') {
            $qb->andWhere('UPPER(n.libelle) = :niveau')
               ->setParameter('niveau', strtoupper(trim($niveau)));
        }

        switch ($tri) {
            case 'note_cours_asc':
                $qb->orderBy('s.noteCours', 'ASC');
                break;
            case 'note_cours_desc':
                $qb->orderBy('s.noteCours', 'DESC');
                break;
            case 'note_consultation_asc':
                $qb->orderBy('s.noteConsultation', 'ASC');
                break;
            case 'note_consultation_desc':
                $qb->orderBy('s.noteConsultation', 'DESC');
                break;
            case 'moyenne_asc':
                $qb->orderBy('s.moyenneGenerale', 'ASC');
                break;
            case 'moyenne_desc':
                $qb->orderBy('s.moyenneGenerale', 'DESC');
                break;
            case 'id_asc':
                $qb->orderBy('s.id', 'ASC');
                break;
            case 'id_desc':
            default:
                $qb->orderBy('s.id', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function findForCsv(?string $search = null, ?string $niveau = null, ?string $tri = 'id_desc'): array
    {
        return $this->searchAndFilter($search, $niveau, $tri);
    }

    public function findStatistics(?string $search = null, ?string $niveau = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.niveauJeu', 'n')
            ->select(
                'COUNT(s.id) AS total',
                'AVG(s.moyenneGenerale) AS average',
                "SUM(CASE WHEN UPPER(n.libelle) = 'FACILE' THEN 1 ELSE 0 END) AS facileCount",
                "SUM(CASE WHEN UPPER(n.libelle) = 'MOYEN' THEN 1 ELSE 0 END) AS moyenCount",
                "SUM(CASE WHEN UPPER(n.libelle) = 'DIFFICILE' THEN 1 ELSE 0 END) AS difficileCount"
            );

        if ($search) {
            $qb->andWhere('(s.id = :searchId OR s.enfantId = :searchId OR s.remarque LIKE :search)')
               ->setParameter('search', '%' . $search . '%')
               ->setParameter('searchId', ctype_digit($search) ? (int) $search : 0);
        }

        if ($niveau !== null && trim($niveau) !== '') {
            $qb->andWhere('UPPER(n.libelle) = :niveau')
               ->setParameter('niveau', strtoupper(trim($niveau)));
        }

        return $qb->getQuery()->getSingleResult();
    }

    public function findResumePourNiveaux(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.niveauJeu', 'n')
            ->select('s.enfantId AS enfantId, s.moyenneGenerale AS moyenneGenerale, n.libelle AS niveau')
            ->where('n.id IS NOT NULL')
            ->orderBy('s.enfantId', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}