<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminUserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users_index')]
    public function index(UserRepository $userRepository): Response
    {
        $entities = $userRepository->findBy([], ['id' => 'DESC']);

        $users = array_map(function ($user) {
            return [
                'id' => $user->getId(),
                'firstname' => $user->getPrenom(),
                'lastname' => $user->getNom(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'status' => match (strtolower((string) $user->getStatus())) {
                    'active' => 'Actif',
                    'deactivated' => 'Inactif',
                    default => $user->getStatus() ?? 'Inactif',
                },
                'registeredAt' => $user->getCreatedAt()?->format('d/m/Y') ?? '-',
            ];
        }, $entities);

        $totalUsers = count($entities);
        $activeUsers = count(array_filter($entities, fn($u) => strtolower((string) $u->getStatus()) === 'active'));
        $inactiveUsers = count(array_filter($entities, fn($u) => strtolower((string) $u->getStatus()) !== 'active'));

        return $this->render('admin/pages/users.html.twig', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'newUsers' => 0,
        ]);
    }
}