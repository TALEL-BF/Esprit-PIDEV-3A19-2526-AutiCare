<?php
// src/Controller/SecurityController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    #[Route('/signin', name: 'app_signin')]
    #[Route('/connexion', name: 'app_connexion')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers son dashboard
        if ($this->getUser()) {
            $user = $this->getUser();
            $role = $user->getRole();
            
            // Redirection selon le rôle (avec les bons noms de routes)
            return match($role) {
                'admin' => $this->redirectToRoute('admin_dashboard'),
                'parent' => $this->redirectToRoute('parent_dashboard'),
                'professeur' => $this->redirectToRoute('professeur_dashboard'),
                'psychologue' => $this->redirectToRoute('psychologue_dashboard'),
                default => $this->redirectToRoute('enfant_dashboard'),
            };
        }

        // Récupérer l'erreur s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier email saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('front/signin/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}