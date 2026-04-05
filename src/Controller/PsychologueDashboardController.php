<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PsychologueDashboardController extends AbstractController
{
    #[Route('/psychologue/dashboard', name: 'psychologue_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('front/psychologue/dashboard.html.twig');
    }
}
