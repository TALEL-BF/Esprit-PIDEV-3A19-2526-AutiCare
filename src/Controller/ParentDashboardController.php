<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParentDashboardController extends AbstractController
{
    #[Route('/parent/dashboard', name: 'parent_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('front/parent/dashboard.html.twig');
    }
}