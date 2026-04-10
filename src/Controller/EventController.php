<?php

namespace App\Controller;

use App\Entity\Event;
use App\Service\GroqService;
use App\Service\WeatherService;
use App\Service\EventAIPredictor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\CacheInterface;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    #[Route('/evenements', name: 'app_evenements')]
   public function index(Request $request, EventRepository $eventRepository, GroqService $groqService, EventAIPredictor $predictor, CacheInterface $cache): Response
    {
        // Récupérer les paramètres de recherche
        $searchTerm = $request->query->get('search');
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        $period = $request->query->get('period');
        $sortBy = $request->query->get('sort', 'date_desc');
        $page = $request->query->getInt('page', 1);
        
        // Si 'type' vaut 'tous', on le met à null
        if ($type === 'tous') {
            $type = null;
        }
        if ($status === 'tous') {
            $status = null;
        }
        if ($period === 'toutes') {
            $period = null;
        }
        
        // Nombre d'événements par page
        $limit = 6;
        
        // Compter le total d'événements avec les filtres
        $totalEvents = $eventRepository->countWithFilters(
            searchTerm: $searchTerm,
            type: $type,
            status: $status,
            period: $period
        );
        
        // Calculer le nombre total de pages
        $totalPages = ceil($totalEvents / $limit);
        
        // S'assurer que la page est valide
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        
        // Récupérer les événements de la page courante
        $queryBuilder = $eventRepository->createFilteredQueryBuilder(
            searchTerm: $searchTerm,
            type: $type,
            status: $status,
            period: $period,
            sortBy: $sortBy
        );
        
        $events = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        return $this->render('front/events/event.html.twig', [
            'events' => $events,
            'searchTerm' => $searchTerm,
            'selectedType' => $type,
            'selectedStatus' => $status,
            'selectedPeriod' => $period,
            'selectedSort' => $sortBy,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents,
            'limit' => $limit
        ]);
    }
    
    /**
     * ROUTE API pour la recherche AJAX (avec pagination)
     */
    #[Route('/api/events/search', name: 'api_events_search')]
    public function searchEvents(Request $request, EventRepository $eventRepository): Response
    {
        // Récupérer les paramètres de la requête AJAX
        $searchTerm = $request->query->get('q');
        $type = $request->query->get('type');
        $page = $request->query->getInt('page', 1);
        
        // Si type = 'all' ou vide, on met à null
        if ($type === 'all' || $type === '' || $type === 'tous') {
            $type = null;
        }
        
        $limit = 6;
        
        // Compter le total
        $totalEvents = $eventRepository->countWithFilters(
            searchTerm: $searchTerm,
            type: $type,
            status: null,
            period: null
        );
        
        $totalPages = ceil($totalEvents / $limit);
        
        // Récupérer les événements paginés
        $queryBuilder = $eventRepository->createFilteredQueryBuilder(
            searchTerm: $searchTerm,
            type: $type,
            status: null,
            period: null,
            sortBy: 'date_desc'
        );
        
        $events = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        // Formater les données pour le JSON
        $data = [];
        foreach ($events as $event) {
            // Calculer l'heure de début seulement
            $heureDebut = $event->getHeureDebut() ? $event->getHeureDebut()->format('H:i') : null;
            
            $data[] = [
                'id' => $event->getIdEvent(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'typeEvent' => $event->getTypeEvent(),
                'lieu' => $event->getLieu(),
                'status' => $event->getStatus(),
                'dateDebut' => $event->getDateDebut()->format('d/m/Y'),
                'heureDebut' => $heureDebut,
                'maxParticipant' => $event->getMaxParticipant(),
                'participantsCount' => 0,
                'image' => $event->getImage(),
            ];
        }
        
        return $this->json([
            'events' => $data,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents
        ]);
    }
    /**
 * Récupère le score IA d'un événement (API)
 */
#[Route('/event/score/{id}', name: 'app_event_score', methods: ['GET'])]
public function getEventScore(int $id, EventRepository $eventRepository, EventAIPredictor $predictor): JsonResponse
{
    $event = $eventRepository->find($id);
    if (!$event) {
        return $this->json(['error' => 'Event not found'], 404);
    }
    
    $score = $predictor->predictAutismScore($event);
    $level = $score >= 70 ? 'Très adapté' : ($score >= 40 ? 'Modérément adapté' : 'Peu adapté');
    
    return $this->json([
        'score' => round($score, 1),
        'level' => $level
    ]);
}

/**
 * Analyse IA détaillée pour un événement (API)
 */
#[Route('/event/analyze/{id}', name: 'app_event_analyze', methods: ['POST'])]
public function analyzeEvent(int $id, EventRepository $eventRepository, GroqService $groqService, EventAIPredictor $predictor): JsonResponse
{
    $event = $eventRepository->find($id);
    if (!$event) {
        return $this->json(['error' => 'Event not found'], 404);
    }
    
    $score = $predictor->predictAutismScore($event);
    
    $eventData = [
        'title' => $event->getTitre(),
        'type' => $event->getTypeEvent(),
        'description' => $event->getDescription(),
        'capacity' => $event->getMaxParticipant(),
        'currentScore' => round($score, 1)
    ];
    
    $analysis = $groqService->generateEventAnalysis($eventData);
    
    return $this->json([
        'success' => true,
        'analysis' => $analysis,
        'score' => round($score, 1)
    ]);
}
/**
 * API pour récupérer la liste "À apporter"
 */
/**
 * API pour récupérer la liste "À apporter"
 */
#[Route('/event/bring/{id}', name: 'app_event_bring', methods: ['GET'])]
public function getBringList(int $id, EventRepository $eventRepository, GroqService $groqService, EventAIPredictor $predictor): JsonResponse
{
    $event = $eventRepository->find($id);
    if (!$event) {
        return $this->json(['error' => 'Event not found'], 404);
    }
    
    $score = $predictor->predictAutismScore($event);
    
    $eventData = [
        'title' => $event->getTitre(),
        'type' => $event->getTypeEvent(),
        'description' => $event->getDescription(),
        'lieu' => $event->getLieu(),  // ← AJOUTÉ
        'capacity' => $event->getMaxParticipant(),
        'score' => round($score, 1)
    ];
    
    $bringList = $groqService->generateBringList($eventData);
    
    return $this->json([
        'success' => true,
        'items' => $bringList
    ]);
}

/**
 * API pour récupérer les conseils personnalisés
 */
#[Route('/event/tips/{id}', name: 'app_event_tips', methods: ['GET'])]
public function getTips(int $id, EventRepository $eventRepository, GroqService $groqService, EventAIPredictor $predictor): JsonResponse
{
    $event = $eventRepository->find($id);
    if (!$event) {
        return $this->json(['error' => 'Event not found'], 404);
    }
    
    $score = $predictor->predictAutismScore($event);
    
    $eventData = [
        'title' => $event->getTitre(),
        'type' => $event->getTypeEvent(),
        'description' => $event->getDescription(),
        'lieu' => $event->getLieu(),  // ← AJOUTÉ
        'capacity' => $event->getMaxParticipant(),
        'score' => round($score, 1)
    ];
    
    $tips = $groqService->generateTips($eventData);
    
    return $this->json([
        'success' => true,
        'tips' => $tips
    ]);
}
   #[Route('/event/{id}', name: 'app_event_show')]
public function show(int $id, EntityManagerInterface $entityManager): Response
{
    $event = $entityManager->getRepository(Event::class)->find($id);
    
    if (!$event) {
        throw $this->createNotFoundException('Événement non trouvé');
    }
    
    // 🔥 CORRECTION : Ajouter le slash au début si nécessaire
    $imagePath = null;
    $imageName = $event->getImage();
    
    if ($imageName) {
        // Si le chemin ne commence pas par /, on l'ajoute
        if (strpos($imageName, '/') === 0) {
            $imagePath = $imageName;
        } else {
            $imagePath = '/' . $imageName;
        }
    }
    
    // ✅ AJOUTE CETTE LIGNE - Décode le planning
    $planningData = json_decode($event->getPlanning(), true);
    
    $sponsors = $event->getSponsors();
    
    $similarEvents = $entityManager->getRepository(Event::class)
        ->createQueryBuilder('e')
        ->where('e.typeEvent = :type')
        ->andWhere('e.idEvent != :id')
        ->setParameter('type', $event->getTypeEvent())
        ->setParameter('id', $id)
        ->setMaxResults(3)
        ->getQuery()
        ->getResult();
    
    return $this->render('front/events/show.html.twig', [
        'event' => $event,
        'planningData' => $planningData,  // ← AJOUTE CETTE LIGNE
        'imagePath' => $imagePath,
        'sponsors' => $sponsors,
        'similarEvents' => $similarEvents
    ]);
}
#[Route('/event/stats/{id}', name: 'app_event_stats', methods: ['GET'])]
public function getEventStats(int $id, EventRepository $eventRepository, EventAIPredictor $predictor): JsonResponse
{
    $event = $eventRepository->find($id);
    if (!$event) {
        return $this->json(['error' => 'Event not found'], 404);
    }
    
    $score = $predictor->predictAutismScore($event);
    
    // Calcul des stats basées sur le score et le type d'événement
    $stats = $this->calculateEventStats($event, $score);
    
    return $this->json($stats);
}

private function calculateEventStats(Event $event, float $score): array
{
    // Niveau sonore
    if ($score >= 70) {
        $noise = ['emoji' => '🔇', 'value' => 'Très calme'];
    } elseif ($score >= 40) {
        $noise = ['emoji' => '🔊', 'value' => 'Modéré'];
    } else {
        $noise = ['emoji' => '📢', 'value' => 'Élevé'];
    }
    
    // Charge sensorielle
    $type = $event->getTypeEvent();
    $sensoryMap = [
        'Atelier' => ['emoji' => '🎨', 'value' => 'Faible'],
        'Conférence' => ['emoji' => '👥', 'value' => 'Moyenne'],
        'Sortie' => ['emoji' => '🚀', 'value' => 'Élevée'],
        'Sport' => ['emoji' => '⚽', 'value' => 'Élevée'],
        'Bien-être' => ['emoji' => '🌿', 'value' => 'Faible'],
    ];
    $sensory = $sensoryMap[$type] ?? ['emoji' => '🧘', 'value' => 'Moyenne'];
    
    // Âge recommandé
    $capacity = $event->getMaxParticipant();
    if ($capacity <= 15) {
        $age = '3-6 ans';
    } elseif ($capacity <= 30) {
        $age = '5-10 ans';
    } else {
        $age = '8-14 ans';
    }
    
    // Niveau d'activité
    $activityMap = [
        'Atelier' => ['emoji' => '🪑', 'value' => 'Assis'],
        'Conférence' => ['emoji' => '🪑', 'value' => 'Assis'],
        'Sortie' => ['emoji' => '🚶', 'value' => 'Debout / Marche'],
        'Sport' => ['emoji' => '🏃', 'value' => 'Actif'],
    ];
    $activity = $activityMap[$type] ?? ['emoji' => '🧘', 'value' => 'Mixte'];
    
    // Recommandation
    if ($score >= 70) {
        $recommendation = ['emoji' => '⭐⭐⭐', 'value' => 'Excellent'];
    } elseif ($score >= 40) {
        $recommendation = ['emoji' => '⭐⭐', 'value' => 'Bon'];
    } else {
        $recommendation = ['emoji' => '⭐', 'value' => 'À adapter'];
    }
    
    return [
        'noise' => $noise,
        'sensory' => $sensory,
        'age' => ['emoji' => '👥', 'value' => $age],
        'activity' => $activity,
        'recommendation' => $recommendation
    ];
}
#[Route('/event/weather/{id}', name: 'app_event_weather', methods: ['GET'])]
public function getWeather(int $id, EventRepository $eventRepository, WeatherService $weatherService): JsonResponse
{
    $event = $eventRepository->find($id);
    
    if (!$event) {
        return $this->json(['error' => 'Event not found'], 404);
    }
    
    $weather = $weatherService->getWeather($event->getLieu());
    
    return $this->json($weather);
}
    
}