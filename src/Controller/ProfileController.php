<?php
// src/Controller/ProfileController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_USER')]
public function edit(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
{
    $user = $this->getUser();

    if ($request->isMethod('POST')) {
        $prenom = trim($request->request->get('prenom'));
        $nom = trim($request->request->get('nom'));
        $phone = trim($request->request->get('phone'));
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');

        // Validation du prénom
        if (strlen($prenom) < 2) {
            $this->addFlash('error', 'Le prénom doit contenir au moins 2 caractères.');
            return $this->redirectToRoute('app_profile_edit');
        }
        if (strlen($prenom) > 50) {
            $this->addFlash('error', 'Le prénom ne peut pas dépasser 50 caractères.');
            return $this->redirectToRoute('app_profile_edit');
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $prenom)) {
            $this->addFlash('error', 'Le prénom ne doit contenir que des lettres.');
            return $this->redirectToRoute('app_profile_edit');
        }

        // Validation du nom
        if (strlen($nom) < 2) {
            $this->addFlash('error', 'Le nom doit contenir au moins 2 caractères.');
            return $this->redirectToRoute('app_profile_edit');
        }
        if (strlen($nom) > 50) {
            $this->addFlash('error', 'Le nom ne peut pas dépasser 50 caractères.');
            return $this->redirectToRoute('app_profile_edit');
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $nom)) {
            $this->addFlash('error', 'Le nom ne doit contenir que des lettres.');
            return $this->redirectToRoute('app_profile_edit');
        }

        // Validation du téléphone (optionnel)
        if (!empty($phone)) {
            if (!preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
                $this->addFlash('error', 'Le numéro de téléphone n\'est pas valide.');
                return $this->redirectToRoute('app_profile_edit');
            }
        }

        // Validation du mot de passe
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('app_profile_edit');
            }
            if (!preg_match('/[A-Z]/', $newPassword)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une majuscule.');
                return $this->redirectToRoute('app_profile_edit');
            }
            if (!preg_match('/[0-9]/', $newPassword)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins un chiffre.');
                return $this->redirectToRoute('app_profile_edit');
            }
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');
                return $this->redirectToRoute('app_profile_edit');
            }
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $this->addFlash('success', 'Mot de passe modifié avec succès.');
        }

        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setPhone($phone);

        $entityManager->flush();

        $this->addFlash('success', 'Profil mis à jour avec succès.');
        return $this->redirectToRoute('app_profile');
    }

    return $this->render('profile/edit.html.twig', [
        'user' => $user,
    ]);
}
}