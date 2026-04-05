<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/psychologue')]
#[IsGranted('ROLE_PSYCHOLOGUE')]
class PsychologueDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'psychologue_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('front/psychologue/dashboard.html.twig');
    }
}