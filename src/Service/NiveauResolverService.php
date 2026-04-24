<?php

namespace App\Service;

use App\Entity\NiveauJeu;
use App\Repository\NiveauJeuRepository;

class NiveauResolverService
{
    public function __construct(private NiveauJeuRepository $niveauJeuRepository)
    {
    }

    public function findByMoyenne(float $moyenne): ?NiveauJeu
    {
        $niveaux = $this->niveauJeuRepository->findAll();

        foreach ($niveaux as $niveau) {
            if (
                $moyenne >= $niveau->getMinMoyenne() &&
                $moyenne <= $niveau->getMaxMoyenne()
            ) {
                return $niveau;
            }
        }

        return null;
    }
}