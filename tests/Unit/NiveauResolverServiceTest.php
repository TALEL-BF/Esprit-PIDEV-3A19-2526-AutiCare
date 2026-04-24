<?php

namespace App\Tests\Unit;

use App\Entity\NiveauJeu;
use App\Repository\NiveauJeuRepository;
use App\Service\NiveauResolverService;
use PHPUnit\Framework\TestCase;

class NiveauResolverServiceTest extends TestCase
{
    public function testFindByMoyenneReturnsMatchingNiveau(): void
    {
        $facile = (new NiveauJeu())
            ->setLibelle('facile')
            ->setMinMoyenne(0)
            ->setMaxMoyenne(10)
            ->setDescription('Niveau facile');

        $moyen = (new NiveauJeu())
            ->setLibelle('moyen')
            ->setMinMoyenne(10.01)
            ->setMaxMoyenne(15)
            ->setDescription('Niveau moyen');

        $repo = $this->createMock(NiveauJeuRepository::class);
        $repo->method('findAll')->willReturn([$facile, $moyen]);

        $service = new NiveauResolverService($repo);

        $result = $service->findByMoyenne(10.5);

        $this->assertInstanceOf(NiveauJeu::class, $result);
        $this->assertSame('MOYEN', $result->getLibelle());
    }

    public function testFindByMoyenneReturnsNullWhenNoRangeMatches(): void
    {
        $facile = (new NiveauJeu())
            ->setLibelle('facile')
            ->setMinMoyenne(0)
            ->setMaxMoyenne(8)
            ->setDescription('Niveau facile');

        $repo = $this->createMock(NiveauJeuRepository::class);
        $repo->method('findAll')->willReturn([$facile]);

        $service = new NiveauResolverService($repo);

        $result = $service->findByMoyenne(18);

        $this->assertNull($result);
    }
}
