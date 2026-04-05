<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'admin_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        $totalUsers = count($users);
        $activeUsers = 0;
        $inactiveUsers = 0;
        $newUsers = 0;
        $thirtyDaysAgo = new \DateTime('-30 days');

        foreach ($users as $user) {
            if ($user->getStatus() === 'active') {
                $activeUsers++;
            } else {
                $inactiveUsers++;
            }

            if ($user->getCreatedAt() && $user->getCreatedAt() > $thirtyDaysAgo) {
                $newUsers++;
            }
        }

        return $this->render('admin/pages/users.html.twig', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'newUsers' => $newUsers,
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
        $email = $user->getEmail();
        $prenom = $user->getPrenom();
        $nom = $user->getNom();
        $phone = $user->getPhone();

        // Validation du prénom
        if (strlen($prenom) < 2) {
            $this->addFlash('error', 'Le prénom doit contenir au moins 2 caractères.');
            return $this->redirectToRoute('admin_users_new');
        }
        if (strlen($prenom) > 50) {
            $this->addFlash('error', 'Le prénom ne peut pas dépasser 50 caractères.');
            return $this->redirectToRoute('admin_users_new');
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $prenom)) {
            $this->addFlash('error', 'Le prénom ne doit contenir que des lettres.');
            return $this->redirectToRoute('admin_users_new');
        }

        // Validation du nom
        if (strlen($nom) < 2) {
            $this->addFlash('error', 'Le nom doit contenir au moins 2 caractères.');
            return $this->redirectToRoute('admin_users_new');
        }
        if (strlen($nom) > 50) {
            $this->addFlash('error', 'Le nom ne peut pas dépasser 50 caractères.');
            return $this->redirectToRoute('admin_users_new');
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $nom)) {
            $this->addFlash('error', 'Le nom ne doit contenir que des lettres.');
            return $this->redirectToRoute('admin_users_new');
        }

        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'L\'adresse email n\'est pas valide.');
            return $this->redirectToRoute('admin_users_new');
        }

        // Vérifier si l'email existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $this->addFlash('error', 'Cet email existe déjà. Veuillez utiliser un autre email.');
            return $this->redirectToRoute('admin_users_new');
        }

        // Validation du téléphone
        if (!empty($phone)) {
            if (!preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
                $this->addFlash('error', 'Le numéro de téléphone n\'est pas valide.');
                return $this->redirectToRoute('admin_users_new');
            }
        }

        $plainPassword = $form->get('plainPassword')->getData();
        
        if ($plainPassword) {
            // Validation du mot de passe
            if (strlen($plainPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('admin_users_new');
            }
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
        $token = $request->request->get('_token');
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $token)) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_users_index');
    }
}