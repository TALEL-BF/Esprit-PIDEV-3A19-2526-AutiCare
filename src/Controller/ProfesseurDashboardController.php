<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfesseurDashboardController extends AbstractController
{
    #[Route('/professeur/dashboard', name: 'professeur_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('front/professeur/dashboard.html.twig');
    }
}
