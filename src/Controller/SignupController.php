<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SignupController extends AbstractController
{
    #[Route('/signup', name: 'app_signup')]
    #[Route('/inscription', name: 'app_inscription')]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $profileType = $request->request->get('profile_type');
            $terms = $request->request->get('terms');
            
            // Validation basique
            $errors = [];
            
            if (!$firstname || !$lastname || !$email || !$password) {
                $errors[] = 'Veuillez remplir tous les champs obligatoires.';
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Veuillez saisir une adresse email valide.';
            }
            
            if (strlen($password) < 6) {
                $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }
            
            if (!$terms) {
                $errors[] = 'Vous devez accepter les conditions d\'utilisation.';
            }
            
            if (count($errors) > 0) {
                $this->addFlash('error', implode('<br>', $errors));
            } else {
                // Ici vous ajouterez la logique d'enregistrement en base de données
                $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_signin');
            }
        }
        
        return $this->render('front/signup/index.html.twig');
    }
}