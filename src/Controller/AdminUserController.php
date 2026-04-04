<?php
// src/Controller/AdminUserController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'admin_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        // Votre code existant
        $entities = $userRepository->findBy([], ['id' => 'DESC']);
        
        $users = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'firstname' => $user->getPrenom(),
                'lastname' => $user->getNom(),
                'email' => $user->getEmail(),
                'role' => ucfirst($user->getRole()),
                'status' => $user->getStatus() === 'active' ? 'Actif' : 'Inactif',
                'registeredAt' => $user->getCreatedAt()?->format('d/m/Y') ?? '-',
            ];
        }, $entities);

        return $this->render('admin/pages/users.html.twig', [
            'users' => $users,
            'totalUsers' => count($entities),
            'activeUsers' => count(array_filter($entities, fn($u) => $u->getStatus() === 'active')),
            'inactiveUsers' => count(array_filter($entities, fn($u) => $u->getStatus() !== 'active')),
            'newUsers' => 0,
        ]);
    }

#[Route('/new', name: 'admin_users_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
{
    $user = new User();
    $user->setStatus('active');
    $user->setCreatedAt(new \DateTime());

    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $plainPassword = $form->get('plainPassword')->getData();
        
        if ($plainPassword) {
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        } else {
            $user->setPassword($passwordHasher->hashPassword($user, 'password123'));
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur ajouté avec succès.');
        return $this->redirectToRoute('admin_users_index');
    }

    return $this->render('admin/user/form.html.twig', [
        'form' => $form->createView(),
        'page_title' => 'Ajouter un utilisateur',
    ]);
}

    #[Route('/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user/form.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Modifier un utilisateur',
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_users_index');
    }
}