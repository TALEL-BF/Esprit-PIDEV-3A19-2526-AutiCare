<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    #[Route('/evenements', name: 'app_evenements')]
    public function index(): Response
    {
        $events = [
            [
                'id' => 1,
                'title' => 'Atelier Art-thérapie & Expression',
                'date' => '2025-05-15',
                'time' => '10h00 - 12h00',
                'location' => 'Tunis',
                'category' => 'Atelier',
                'description' => 'Séance collective d\'art-thérapie pour développer l\'expression émotionnelle.',
                'speakers' => 'Dr. Lina Mansouri',
                'capacity' => 12,
                'taken' => 8,
                'duration' => '2h',
                'emoji' => '🎭',
                'badge' => 'Places limitées'
            ],
            [
                'id' => 2,
                'title' => 'Conférence : Comprendre l\'Autisme',
                'date' => '2025-05-22',
                'time' => '14h00 - 17h00',
                'location' => 'En ligne',
                'category' => 'Conférence',
                'description' => 'Webinaire avec des experts en TSA.',
                'speakers' => 'Dr. Amira Ben Ali',
                'capacity' => 100,
                'taken' => 64,
                'duration' => '3h',
                'emoji' => '🎤',
                'badge' => ''
            ],
            [
                'id' => 3,
                'title' => 'Journée Sportive Inclusive',
                'date' => '2025-06-05',
                'time' => '9h00 - 13h00',
                'location' => 'Sfax',
                'category' => 'Sport',
                'description' => 'Activités sportives adaptées pour favoriser l\'intégration sociale.',
                'speakers' => 'M. Karim Hamdi',
                'capacity' => 30,
                'taken' => 22,
                'duration' => '4h',
                'emoji' => '⚽',
                'badge' => ''
            ],
            [
                'id' => 4,
                'title' => 'Atelier Formation Parents',
                'date' => '2025-06-12',
                'time' => '15h00 - 18h00',
                'location' => 'Sousse',
                'category' => 'Formation',
                'description' => 'Formation pour aider les parents à mieux soutenir leur enfant.',
                'speakers' => 'Mme. Sonia Trabelsi',
                'capacity' => 20,
                'taken' => 15,
                'duration' => '3h',
                'emoji' => '📚',
                'badge' => 'Nouveau'
            ],
            [
                'id' => 5,
                'title' => 'Forum Emploi Inclusif 2025',
                'date' => '2025-06-20',
                'time' => '9h00 - 16h00',
                'location' => 'Tunis',
                'category' => 'Formation',
                'description' => 'Rencontrez des entreprises partenaires.',
                'speakers' => '8 entreprises partenaires',
                'capacity' => 200,
                'taken' => 140,
                'duration' => '7h',
                'emoji' => '💼',
                'badge' => 'Populaire'
            ],
            [
                'id' => 6,
                'title' => 'Concert & Musicothérapie',
                'date' => '2025-07-03',
                'time' => '10h00 - 12h30',
                'location' => 'En ligne',
                'category' => 'Bien-être',
                'description' => 'Séance de musicothérapie collective en ligne.',
                'speakers' => 'M. Yassine Bouzid',
                'capacity' => 50,
                'taken' => 18,
                'duration' => '2h30',
                'emoji' => '🎵',
                'badge' => ''
            ],
            [
                'id' => 7,
                'title' => 'Atelier Communication PECS',
                'date' => '2025-05-08',
                'time' => '14h00 - 16h00',
                'location' => 'Tunis',
                'category' => 'Atelier',
                'description' => 'Apprenez à utiliser le système PECS.',
                'speakers' => 'Mme. Sonia Trabelsi',
                'capacity' => 10,
                'taken' => 7,
                'duration' => '2h',
                'emoji' => '💬',
                'badge' => 'Complet bientôt'
            ],
            [
                'id' => 8,
                'title' => 'Séance de Yoga Relaxation',
                'date' => '2025-05-25',
                'time' => '10h00 - 11h30',
                'location' => 'En ligne',
                'category' => 'Bien-être',
                'description' => 'Séance de yoga adaptée pour la relaxation.',
                'speakers' => 'Mme. Leila Ben Amor',
                'capacity' => 20,
                'taken' => 12,
                'duration' => '1h30',
                'emoji' => '🧘',
                'badge' => ''
            ],
        ];

        return $this->render('front/events/index.html.twig', [
            'events' => $events
        ]);
    }
}