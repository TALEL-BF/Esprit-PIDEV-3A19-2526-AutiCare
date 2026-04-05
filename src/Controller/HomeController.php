<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('front/home/index.html.twig');
    }

    #[Route('/planning', name: 'app_planning')]
    public function planning(): Response
    {
        $sessions = [
            ['day' => 'Lundi', 'time' => '09h00', 'title' => 'Communication Alternatives', 'therapist' => 'Dr. Sarah Ben Ali', 'specialty' => 'Orthophoniste', 'type' => 'cours', 'icon' => '👩‍🏫', 'level' => 'Débutant', 'description' => 'Apprentissage des outils de communication alternative et augmentative.', 'available' => true],
            ['day' => 'Lundi', 'time' => '14h00', 'title' => 'Séance de Psychologie', 'therapist' => 'Dr. Ahmed Mansour', 'specialty' => 'Psychologue', 'type' => 'psychologue', 'icon' => '🧠', 'level' => 'Tous niveaux', 'description' => 'Accompagnement psychologique individuel et familial.', 'available' => true],
            ['day' => 'Mardi', 'time' => '10h00', 'title' => 'Compétences Sociales', 'therapist' => 'Dr. Leila Trabelsi', 'specialty' => 'Éducatrice spécialisée', 'type' => 'cours', 'icon' => '👥', 'level' => 'Intermédiaire', 'description' => 'Développement des habiletés sociales et relationnelles.', 'available' => true],
            ['day' => 'Mardi', 'time' => '15h00', 'title' => 'Thérapie Comportementale', 'therapist' => 'Dr. Karim Bouaziz', 'specialty' => 'Psychologue comportemental', 'type' => 'psychologue', 'icon' => '💬', 'level' => 'Tous niveaux', 'description' => 'Techniques ABA et gestion des comportements adaptés.', 'available' => false],
            ['day' => 'Mercredi', 'time' => '09h30', 'title' => 'Éveil Sensoriel', 'therapist' => 'Mme. Fatma Hamdi', 'specialty' => 'Ergothérapeute', 'type' => 'cours', 'icon' => '🎨', 'level' => 'Débutant', 'description' => 'Activités sensorielles pour stimuler les perceptions.', 'available' => true],
            ['day' => 'Jeudi', 'time' => '11h00', 'title' => 'Autonomie Quotidienne', 'therapist' => 'Dr. Nadia Ferchichi', 'specialty' => 'Éducatrice spécialisée', 'type' => 'cours', 'icon' => '🌟', 'level' => 'Avancé', 'description' => 'Apprentissage des gestes du quotidien et de l\'indépendance.', 'available' => true],
            ['day' => 'Jeudi', 'time' => '16h00', 'title' => 'Suivi Psychologique', 'therapist' => 'Dr. Ahmed Mansour', 'specialty' => 'Psychologue', 'type' => 'psychologue', 'icon' => '🧠', 'level' => 'Tous niveaux', 'description' => 'Session de suivi et bilan de progression.', 'available' => true],
            ['day' => 'Vendredi', 'time' => '10h00', 'title' => 'Atelier Créativité', 'therapist' => 'Mme. Fatma Hamdi', 'specialty' => 'Ergothérapeute', 'type' => 'cours', 'icon' => '🎭', 'level' => 'Tous niveaux', 'description' => 'Expression artistique et créative comme outil thérapeutique.', 'available' => true],
            ['day' => 'Samedi', 'time' => '10h00', 'title' => 'Atelier Parents', 'therapist' => 'Dr. Sarah Ben Ali', 'specialty' => 'Orthophoniste', 'type' => 'cours', 'icon' => '👨‍👩‍👧', 'level' => 'Parents', 'description' => 'Accompagnement et formation des parents aux techniques adaptées.', 'available' => true],
        ];

        $stats = [
            ['icon' => 'fas fa-chalkboard-teacher', 'color' => 'primary', 'value' => '12', 'label' => 'Cours par semaine'],
            ['icon' => 'fas fa-user-md', 'color' => 'danger', 'value' => '8', 'label' => 'Psychologues'],
            ['icon' => 'fas fa-users', 'color' => 'success', 'value' => '150+', 'label' => 'Familles accompagnées'],
            ['icon' => 'fas fa-clock', 'color' => 'warning', 'value' => '6j/7', 'label' => 'Disponibilité'],
        ];

        return $this->render('front/planning/index.html.twig', [
            'sessions' => $sessions,
            'stats'    => $stats,
        ]);
    }
}