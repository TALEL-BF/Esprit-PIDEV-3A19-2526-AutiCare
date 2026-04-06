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
        return $this->niveauJeuRepository->findOneByMoyenneRange($moyenne);
    }
}
