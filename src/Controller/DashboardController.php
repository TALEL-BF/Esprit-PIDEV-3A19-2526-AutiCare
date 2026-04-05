<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/dashboard/parent', name: 'parent_dashboard')]
    #[IsGranted('ROLE_PARENT')]
    public function parentDashboard(): Response
    {
        return $this->render('front/parent/dashboard.html.twig');
    }

    #[Route('/dashboard/enfant', name: 'enfant_dashboard')]
    #[IsGranted('ROLE_ENFANT')]
    public function enfantDashboard(): Response
    {
        return $this->render('front/enfant/dashboard.html.twig');
    }

    #[Route('/dashboard/professeur', name: 'professeur_dashboard')]
    #[IsGranted('ROLE_PROFESSEUR')]
    public function professeurDashboard(): Response
    {
        return $this->render('front/professeur/dashboard.html.twig');
    }

    #[Route('/dashboard/psychologue', name: 'psychologue_dashboard')]
    #[IsGranted('ROLE_PSYCHOLOGUE')]
    public function psychologueDashboard(): Response
    {
        return $this->render('front/psychologue/dashboard.html.twig');
    }
}