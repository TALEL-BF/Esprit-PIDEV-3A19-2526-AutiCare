<?php
// src/Controller/ProfesseurDashboardController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/professeur')]
#[IsGranted('ROLE_PROFESSEUR')]
class ProfesseurDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'professeur_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('front/professeur/dashboard.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}