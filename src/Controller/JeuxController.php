<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JeuxController extends AbstractController
{
    #[Route('/jeux', name: 'app_jeux')]
    public function index(): Response
    {
        // Données des jeux
        $games = [
            [
                'id' => 1,
                'title' => 'Jeu des Émotions',
                'description' => 'Identifiez et nommez les émotions à travers des cartes illustrées et des scénarios du quotidien.',
                'category' => 'Émotions',
                'category_color' => 'secondary',
                'age' => '3-7 ans',
                'difficulty' => 2, // 1-3
                'icon' => '😊',
                'gradient' => 'linear-gradient(135deg,#ff9a9e,#fecfef)',
                'color' => 'secondary'
            ],
            [
                'id' => 2,
                'title' => 'Puzzle des Formes',
                'description' => 'Assemblez des pièces de puzzle interactives en progressant par niveaux de difficulté.',
                'category' => 'Logique',
                'category_color' => 'primary',
                'age' => '5-12 ans',
                'difficulty' => 3,
                'icon' => '🧩',
                'gradient' => 'linear-gradient(135deg,#a8edea,#fed6e3)',
                'color' => 'primary'
            ],
            [
                'id' => 3,
                'title' => 'Parole & Images PECS',
                'description' => 'Apprenez à communiquer via des pictogrammes et des cartes visuelles interactives.',
                'category' => 'Communication',
                'category_color' => 'success',
                'age' => '4-10 ans',
                'difficulty' => 1,
                'icon' => '🗣️',
                'gradient' => 'linear-gradient(135deg,#ffecd2,#fcb69f)',
                'color' => 'success'
            ],
            [
                'id' => 4,
                'title' => 'Memory Coloré',
                'description' => 'Entraînez la mémoire visuelle et la concentration avec des cartes colorées adaptées TSA.',
                'category' => 'Mémoire',
                'category_color' => 'warning',
                'age' => '6-15 ans',
                'difficulty' => 2,
                'icon' => '🧠',
                'gradient' => 'linear-gradient(135deg,#d4fc79,#96e6a1)',
                'color' => 'warning'
            ],
            [
                'id' => 5,
                'title' => 'Coloriage Sensoriel',
                'description' => 'Coloriage interactif avec retour sensoriel sonore et tactile pour développer la motricité fine.',
                'category' => 'Motricité',
                'category_color' => 'info',
                'age' => '3-8 ans',
                'difficulty' => 1,
                'icon' => '✋',
                'gradient' => 'linear-gradient(135deg,#89f7fe,#66a6ff)',
                'color' => 'info'
            ],
            [
                'id' => 6,
                'title' => 'Scénarios Sociaux',
                'description' => 'Simulez des situations sociales et apprenez les comportements appropriés dans chaque contexte.',
                'category' => 'Social',
                'category_color' => 'primary',
                'age' => '8-16 ans',
                'difficulty' => 3,
                'icon' => '👥',
                'gradient' => 'linear-gradient(135deg,#e0c3fc,#8ec5fc)',
                'color' => 'primary'
            ]
        ];

        // Catégories pour les filtres
        $categories = ['Tous', 'Communication', 'Émotions', 'Motricité', 'Mémoire', 'Logique', 'Social'];

        return $this->render('front/jeux/index.html.twig', [
            'games' => $games,
            'categories' => $categories
        ]);
    }
}