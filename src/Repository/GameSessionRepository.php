<?php

namespace App\Repository;

use App\Entity\GameSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameSession>
 */
class GameSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameSession::class);
    }

    /**
     * @return GameSession[]
     */
    public function findByEnfantAndPeriod(int $enfantId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('gs')
            ->andWhere('gs.enfantId = :enfantId')
            ->andWhere('gs.playedAt >= :start')
            ->andWhere('gs.playedAt <= :end')
            ->setParameter('enfantId', $enfantId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('gs.playedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return GameSession[]
     */
    public function findRecentByEnfant(int $enfantId, int $limit = 15): array
    {
        return $this->createQueryBuilder('gs')
            ->andWhere('gs.enfantId = :enfantId')
            ->setParameter('enfantId', $enfantId)
            ->orderBy('gs.playedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
