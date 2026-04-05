<?php
// src/Controller/SignupController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SignupController extends AbstractController
{
    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
public function index(
    Request $request,
    EntityManagerInterface $entityManager,
    UserPasswordHasherInterface $passwordHasher,
    UserRepository $userRepository
): Response {
    if ($request->isMethod('POST')) {
        $firstname = trim($request->request->get('firstname'));
        $lastname = trim($request->request->get('lastname'));
        $email = trim($request->request->get('email'));
        $password = $request->request->get('password');
        $confirmPassword = $request->request->get('confirm_password');
        $profileType = $request->request->get('profile_type', 'enfant');

        // Validation du prénom
        if (strlen($firstname) < 2) {
            $this->addFlash('error', 'Le prénom doit contenir au moins 2 caractères.');
            return $this->redirectToRoute('app_signup');
        }
        if (strlen($firstname) > 50) {
            $this->addFlash('error', 'Le prénom ne peut pas dépasser 50 caractères.');
            return $this->redirectToRoute('app_signup');
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $firstname)) {
            $this->addFlash('error', 'Le prénom ne doit contenir que des lettres.');
            return $this->redirectToRoute('app_signup');
        }

        // Validation du nom
        if (strlen($lastname) < 2) {
            $this->addFlash('error', 'Le nom doit contenir au moins 2 caractères.');
            return $this->redirectToRoute('app_signup');
        }
        if (strlen($lastname) > 50) {
            $this->addFlash('error', 'Le nom ne peut pas dépasser 50 caractères.');
            return $this->redirectToRoute('app_signup');
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $lastname)) {
            $this->addFlash('error', 'Le nom ne doit contenir que des lettres.');
            return $this->redirectToRoute('app_signup');
        }

        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'L\'adresse email n\'est pas valide.');
            return $this->redirectToRoute('app_signup');
        }

        // Vérifier si l'email existe déjà
        if ($userRepository->findOneBy(['email' => $email])) {
            $this->addFlash('error', 'Cet email existe déjà.');
            return $this->redirectToRoute('app_signup');
        }

        // Validation du mot de passe
        if (strlen($password) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('app_signup');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins une majuscule.');
            return $this->redirectToRoute('app_signup');
        }
        if (!preg_match('/[0-9]/', $password)) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins un chiffre.');
            return $this->redirectToRoute('app_signup');
        }
        if ($password !== $confirmPassword) {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_signup');
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setPrenom($firstname);
        $user->setNom($lastname);
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRole($profileType);
        $user->setStatus('active');
        $user->setCreatedAt(new \DateTime());
        
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Compte créé avec succès. Veuillez vous connecter.');
        return $this->redirectToRoute('app_signin');
    }

    return $this->render('front/signup/index.html.twig');
}
}