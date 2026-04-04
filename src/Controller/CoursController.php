<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CoursController extends AbstractController
{
    #[Route('/cours', name: 'app_cours')]
    public function index(): Response
    {
        // Données des cours
        $courses = [
            [
                'id' => 1,
                'title' => 'English For Today',
                'description' => 'Apprenez l\'anglais à votre rythme avec des méthodes adaptées.',
                'category' => 'communication',
                'price' => 60.99,
                'rating' => 4.5,
                'reviews' => 124,
                'sessions' => 30,
                'lessons' => 11,
                'hours' => 60,
                'instructor' => 'Mary Mordern',
                'instructor_title' => 'Arts Designer',
                'emoji' => '🗣️',
                'badge' => '⭐ Populaire'
            ],
            [
                'id' => 2,
                'title' => 'Arts Graphiques',
                'description' => 'Explorez le dessin et les arts visuels.',
                'category' => 'motricite',
                'price' => 60.99,
                'rating' => 5.0,
                'reviews' => 89,
                'sessions' => 30,
                'lessons' => 11,
                'hours' => 60,
                'instructor' => 'Mary Mordern',
                'instructor_title' => 'Arts Designer',
                'emoji' => '🎨',
                'badge' => ''
            ],
            [
                'id' => 3,
                'title' => 'Sciences Générales',
                'description' => 'Découvrez le monde avec curiosité.',
                'category' => 'autonomie',
                'price' => 60.99,
                'rating' => 4.5,
                'reviews' => 156,
                'sessions' => 30,
                'lessons' => 11,
                'hours' => 60,
                'instructor' => 'Mary Mordern',
                'instructor_title' => 'Arts Designer',
                'emoji' => '🔬',
                'badge' => ''
            ],
            [
                'id' => 4,
                'title' => 'Communication Visuelle',
                'description' => 'Utilisez des supports visuels adaptés.',
                'category' => 'communication',
                'price' => 49.99,
                'rating' => 5.0,
                'reviews' => 67,
                'sessions' => 24,
                'lessons' => 8,
                'hours' => 45,
                'instructor' => 'Sophie Martin',
                'instructor_title' => 'Communication Expert',
                'emoji' => '📸',
                'badge' => '✨ Nouveau'
            ],
            [
                'id' => 5,
                'title' => 'Motricité Fine Avancée',
                'description' => 'Exercices progressifs pour améliorer la coordination.',
                'category' => 'motricite',
                'price' => 55.99,
                'rating' => 4.5,
                'reviews' => 203,
                'sessions' => 20,
                'lessons' => 10,
                'hours' => 50,
                'instructor' => 'Thomas Dubois',
                'instructor_title' => 'Ergothérapeute',
                'emoji' => '🤲',
                'badge' => ''
            ],
            [
                'id' => 6,
                'title' => 'Social Skills',
                'description' => 'Développez vos compétences sociales.',
                'category' => 'social',
                'price' => 65.99,
                'rating' => 5.0,
                'reviews' => 178,
                'sessions' => 28,
                'lessons' => 12,
                'hours' => 55,
                'instructor' => 'Laura Bernard',
                'instructor_title' => 'Psychologue Social',
                'emoji' => '🤝',
                'badge' => ''
            ],
        ];

        return $this->render('front/cours/index.html.twig', [
            'courses' => $courses
        ]);
    }
}