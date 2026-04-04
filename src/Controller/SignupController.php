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
            $firstname = trim((string) $request->request->get('firstname'));
            $lastname = trim((string) $request->request->get('lastname'));
            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');
            $confirmPassword = (string) $request->request->get('confirm_password');
            $profileType = (string) $request->request->get('profile_type', 'enfant');

            if ($firstname === '' || $lastname === '' || $email === '' || $password === '') {
                $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            } elseif ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } elseif ($userRepository->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Cet email existe déjà.');
            } else {
                $user = new User();
                $user->setPrenom($firstname);
                $user->setNom($lastname);
                $user->setEmail($email);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setRole($profileType);
                $user->setStatus('active');
                $user->setCreatedAt(new \DateTime());  // ✅ CORRECTION ICI
                
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Compte créé avec succès. Veuillez vous connecter.');
                return $this->redirectToRoute('app_signin');
            }
        }

        return $this->render('front/signup/index.html.twig');
    }
}