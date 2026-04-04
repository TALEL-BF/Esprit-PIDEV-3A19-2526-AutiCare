<?php
// src/Controller/ParentDashboardController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parent')]
#[IsGranted('ROLE_PARENT')]
class ParentDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'parent_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('front/parent/dashboard.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}