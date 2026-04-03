<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TherapieController extends AbstractController
{
    #[Route('/therapie', name: 'app_therapie')]
    public function index(): Response
    {
        // Données des types de thérapie
        $sessionTypes = [
            [
                'id' => 1,
                'title' => 'Thérapie ABA',
                'description' => 'Analyse du Comportement Appliqué — développement des compétences et réduction des comportements difficiles.',
                'icon' => 'fa-brain',
                'color' => 'primary',
                'badges' => ['En ligne', 'Présentiel']
            ],
            [
                'id' => 2,
                'title' => 'Orthophonie',
                'description' => 'Rééducation du langage oral, écrit et de la communication — pour tous les âges et niveaux.',
                'icon' => 'fa-comment-dots',
                'color' => 'secondary',
                'badges' => ['En ligne', 'Présentiel']
            ],
            [
                'id' => 3,
                'title' => 'Ergothérapie',
                'description' => 'Développement de l\'autonomie dans les activités quotidiennes et adaptation de l\'environnement.',
                'icon' => 'fa-hands',
                'color' => 'success',
                'badges' => ['En ligne', 'Présentiel']
            ],
            [
                'id' => 4,
                'title' => 'Musicothérapie',
                'description' => 'Utilisation de la musique pour favoriser la communication, l\'expression émotionnelle et la relaxation.',
                'icon' => 'fa-music',
                'color' => 'warning',
                'badges' => ['En ligne']
            ],
            [
                'id' => 5,
                'title' => 'Art-thérapie',
                'description' => 'Expression créative par les arts plastiques pour explorer les émotions et développer la confiance en soi.',
                'icon' => 'fa-palette',
                'color' => 'info',
                'badges' => ['En ligne', 'Présentiel']
            ],
            [
                'id' => 6,
                'title' => 'Zoothérapie',
                'description' => 'Médiation animale pour améliorer les interactions sociales et l\'équilibre émotionnel.',
                'icon' => 'fa-dog',
                'color' => 'danger',
                'badges' => ['Présentiel']
            ]
        ];

        // Données des thérapeutes
        $therapists = [
            [
                'id' => 1,
                'name' => 'Dr. Amira Ben Ali',
                'title' => 'Spécialiste ABA',
                'description' => '10 ans d\'expérience en thérapie comportementale pour TSA.',
                'emoji' => '👩‍⚕️',
                'color' => 'primary',
                'badges' => ['ABA', 'PECS']
            ],
            [
                'id' => 2,
                'name' => 'M. Karim Hamdi',
                'title' => 'Ergothérapeute',
                'description' => 'Spécialisé en intégration sensorielle et autonomie quotidienne.',
                'emoji' => '👨‍⚕️',
                'color' => 'secondary',
                'badges' => ['Ergothérapie', 'Sensoriel']
            ],
            [
                'id' => 3,
                'name' => 'Mme. Sonia Trabelsi',
                'title' => 'Orthophoniste',
                'description' => 'Experte en communication augmentée et langage TSA depuis 8 ans.',
                'emoji' => '👩‍🏫',
                'color' => 'success',
                'badges' => ['Orthophonie', 'CAA']
            ],
            [
                'id' => 4,
                'name' => 'Dr. Lina Mansouri',
                'title' => 'Art-thérapeute',
                'description' => 'Psychologue spécialisée en art-thérapie et gestion des émotions TSA.',
                'emoji' => '👩‍🎨',
                'color' => 'warning',
                'badges' => ['Art-thérapie', 'Psychologie']
            ]
        ];

        return $this->render('front/therapie/index.html.twig', [
            'sessionTypes' => $sessionTypes,
            'therapists' => $therapists
        ]);
    }
}