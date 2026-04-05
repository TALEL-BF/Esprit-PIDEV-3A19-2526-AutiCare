<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EnfantDashboardController extends AbstractController
{
    #[Route('/enfant/dashboard', name: 'enfant_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('front/enfant/dashboard.html.twig');
    }
}