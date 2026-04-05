<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/pages/dashboard.html.twig');
    }

   // ==================== UTILISATEURS ====================
#[Route('/admin/users', name: 'admin_users')]
public function users(): Response
{
    return $this->render('admin/pages/users.html.twig');
}

#[Route('/admin/users/roles', name: 'admin_users_roles')]
public function usersRoles(): Response
{
    return $this->render('admin/pages/users_roles.html.twig');
}


// ==================== COURS ====================
#[Route('/admin/cours', name: 'admin_cours')]
public function cours(): Response
{
    return $this->render('admin/pages/cours.html.twig');
}

#[Route('/admin/cours/modules', name: 'admin_cours_modules')]
public function coursModules(): Response
{
    return $this->render('admin/pages/cours_modules.html.twig');
}
// ==================== ÉVÉNEMENTS ====================
#[Route('/admin/evenements', name: 'admin_evenements')]
public function evenements(): Response
{
    return $this->render('admin/pages/evenements.html.twig');
}

#[Route('/admin/sponsors', name: 'admin_sponsors')]
public function sponsors(): Response
{
    return $this->render('admin/pages/sponsors.html.twig');
}

// ==================== CONSULTATIONS ====================
#[Route('/admin/therapie', name: 'admin_therapie')]
public function therapie(): Response
{
    return $this->render('admin/pages/therapie.html.twig');
}

#[Route('/admin/seance-static', name: 'admin_seance_static')]
public function seance(): Response
{
    return $this->redirectToRoute('admin_seance');
}

// ==================== EMPLOIS ====================
#[Route('/admin/planning-static', name: 'admin_planning_static')]
public function planning(): Response
{
    return $this->redirectToRoute('admin_planning');
}

#[Route('/admin/salle', name: 'admin_salle')]
public function salle(): Response
{
    return $this->render('admin/pages/salle.html.twig');
}

// ==================== JEU ====================
#[Route('/admin/jeu', name: 'admin_jeu')]
public function jeu(): Response
{
    return $this->render('admin/pages/jeu.html.twig');
}

#[Route('/admin/jeu/score', name: 'admin_jeu_score')]
public function jeuScore(): Response
{
    return $this->render('admin/pages/jeu_score.html.twig');
}
}