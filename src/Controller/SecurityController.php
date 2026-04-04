<?php
// src/Controller/SecurityController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    #[Route('/signin', name: 'app_signin')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $user = $this->getUser();
            $role = $user->getRole();
            
            // Redirection selon le rôle
            return match($role) {
                'admin' => $this->redirectToRoute('admin_dashboard'),
                'parent' => $this->redirectToRoute('parent_dashboard'),
                'professeur' => $this->redirectToRoute('professeur_dashboard'),
                'psychologue' => $this->redirectToRoute('psychologue_dashboard'),
                default => $this->redirectToRoute('enfant_dashboard'),
            };
        }

        return $this->render('front/signin/index.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {}
}