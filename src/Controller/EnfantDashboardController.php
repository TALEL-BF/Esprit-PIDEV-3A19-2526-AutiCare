<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enfant')]
#[IsGranted('ROLE_ENFANT')]
class EnfantDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'enfant_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('front/enfant/dashboard.html.twig');
    }
}