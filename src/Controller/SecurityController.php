<?php
// src/Controller/SecurityController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
            
            return match($role) {
                'admin' => $this->redirectToRoute('admin_dashboard'),
                'parent' => $this->redirectToRoute('parent_dashboard'),
                'professeur' => $this->redirectToRoute('professeur_dashboard'),
                'psychologue' => $this->redirectToRoute('psychologue_dashboard'),
                default => $this->redirectToRoute('enfant_dashboard'),
            };
        }

        $error = $authenticationUtils->getLastAuthenticationError();
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