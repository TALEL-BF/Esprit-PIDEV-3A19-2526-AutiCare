<?php

namespace App\Controller\event;

use App\Entity\Event;
use Geocoder\StatefulGeocoder;
use Geocoder\Provider\Nominatim\Nominatim;

use Geocoder\Geocoder;
use Http\Discovery\Psr18ClientDiscovery;
use App\Services\GroqService;
use App\Services\WeatherService;
use App\Services\EventAIPredictor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\CacheInterface;
use App\Repository\EventRepository;


use Endroid\QrCode\Writer\SvgWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;



use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


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
        'limit' => $limit,
        'openroute_api_key' => $this->getParameter('openroute_api_key'),
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
                'dateDebut' => $event->getDateDebut()->format('Y-m-d'),
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
    #[Route('/api/route', name: 'api_route', methods: ['POST'])]
public function getRoute(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $startLat = $data['startLat'] ?? null;
    $startLng = $data['startLng'] ?? null;
    $endLat = $data['endLat'] ?? null;
    $endLng = $data['endLng'] ?? null;
    $profile = $data['profile'] ?? null; // ← AJOUTE CETTE LIGNE
    
    if (!$startLat || !$startLng || !$endLat || !$endLng) {
        return $this->json(['error' => 'Missing coordinates'], 400);
    }
    
    $apiKey = $this->getParameter('openroute_api_key');
    $coordinates = [
        [(float)$startLng, (float)$startLat],
        [(float)$endLng, (float)$endLat]
    ];
    
   
    if ($profile === 'pedestrian') {
        $route = $this->calculateRoute($apiKey, $coordinates, 'pedestrian');
        return $this->json([
            'success' => true,
            'route' => $route,
            'profile' => $profile
        ]);
    }
    
    if ($profile === 'fast') {
        $route = $this->calculateRoute($apiKey, $coordinates, 'fast');
        return $this->json([
            'success' => true,
            'route' => $route,
            'profile' => $profile
        ]);
    }
    
    if ($profile === 'calm') {
        $route = $this->calculateRoute($apiKey, $coordinates, 'calm');
        return $this->json([
            'success' => true,
            'route' => $route,
            'profile' => $profile
        ]);
    }
    
   
    $fastRoute = $this->calculateRoute($apiKey, $coordinates, 'fast');
    $calmRoute = $this->calculateRoute($apiKey, $coordinates, 'calm');
    $pedestrianRoute = $this->calculateRoute($apiKey, $coordinates, 'pedestrian');
    
    return $this->json([
        'success' => true,
        'fast' => $fastRoute,
        'calm' => $calmRoute,
        'pedestrian' => $pedestrianRoute
    ]);
}

    /**
     * Calculate a route using OpenRouteService API
     */
  private function calculateRoute($apiKey, $coordinates, $type): array
{
   
    $profile = 'driving-car'; // par défaut : voiture
    
    if ($type === 'pedestrian') {
        $profile = 'foot-walking'; // piéton
    }
    
    $url = 'https://api.openrouteservice.org/v2/directions/' . $profile . '/geojson';
    
    $postFields = [
        'coordinates' => $coordinates,
        'units' => 'km',
        'language' => 'fr'
    ];
    
    if ($type === 'calm') {
        $postFields['options'] = [
            'avoid_features' => ['motorway', 'trunk'],
            'preference' => 'shortest'
        ];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode === 200) {
        $routeData = json_decode($response, true);
        return [
            'coordinates' => $routeData['features'][0]['geometry']['coordinates'] ?? [],
            'distance' => $routeData['features'][0]['properties']['segments'][0]['distance'] ?? 0,
            'duration' => $routeData['features'][0]['properties']['segments'][0]['duration'] ?? 0
        ];
    }
    
    // Fallback : ligne droite
    $distance = $this->calculateDistance($coordinates[0][1], $coordinates[0][0], $coordinates[1][1], $coordinates[1][0]);
    $speed = ($type === 'pedestrian') ? 5 : 50; // 5 km/h piéton, 50 km/h voiture
    return [
        'coordinates' => $coordinates,
        'distance' => $distance * 1000,
        'duration' => ($distance / $speed) * 3600
    ];
}

   
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
    
private function getStreetName($lat, $lng): string
{
    $url = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lng}&format=json&zoom=18&addressdetails=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AutiCareApp/1.0 (contact@auticare.com)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("🔍 Nominatim - Lat: {$lat}, Lng: {$lng} - HTTP: {$httpCode}");
    
    if ($response && $httpCode === 200) {
        $data = json_decode($response, true);
        
       
        $streetName = '';
        
        if (isset($data['address']['road'])) {
            $streetName = $data['address']['road'];
        } elseif (isset($data['address']['pedestrian'])) {
            $streetName = $data['address']['pedestrian'];
        } elseif (isset($data['address']['footway'])) {
            $streetName = $data['address']['footway'];
        } elseif (isset($data['address']['suburb'])) {
            $streetName = $data['address']['suburb'];
        } elseif (isset($data['address']['city'])) {
            $streetName = $data['address']['city'];
        }
        
        if (!empty($streetName)) {
            error_log("✅ Rue trouvée: " . $streetName);
            return $streetName;
        }
    }
    
    error_log("❌ Aucun nom trouvé - lat: {$lat}, lng: {$lng}");
    return 'Route inconnue';
}
/**
 * Extract unique street names from route coordinates (sample every 10 points)
 */
private function extractStreetNames($coordinates): array
{
    $streets = [];
    
   
    $targetCount = 5; // Nombre de rues souhaité
    $totalPoints = count($coordinates);
    $step = max(1, floor($totalPoints / $targetCount));
    
    for ($i = 0; $i < $totalPoints; $i += $step) {
        $coord = $coordinates[$i];
        $streetName = $this->getStreetName($coord[1], $coord[0]);
        
        if ($streetName !== 'Route inconnue' && !in_array($streetName, $streets)) {
            $streets[] = $streetName;
        }
        
        // Stop quand on a 5 rues
        if (count($streets) >= $targetCount) {
            break;
        }
    }
    
    // Si pas assez de rues, compléter avec des noms génériques
    if (count($streets) < $targetCount) {
        $defaults = ['Rue Principale', 'Avenue Centrale', 'Boulevard', 'Route Nationale', 'Chemin Rural'];
        for ($i = 0; $i < $targetCount - count($streets); $i++) {
            $streets[] = $defaults[$i];
        }
    }
    
    error_log("📊 Rues extraites: " . count($streets));
    return $streets;
}
#[Route('/api/ai/analyze-route', name: 'api_ai_analyze_route', methods: ['POST'])]
public function analyzeRouteWithAI(Request $request, GroqService $groqService): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $coordinates = $data['coordinates'] ?? [];
    $eventTitle = $data['eventTitle'] ?? '';
    $currentHour = date('H');
    
    if (empty($coordinates)) {
        return $this->json(['error' => 'No coordinates'], 400);
    }
    
    // Extract street names from route
    $streets = $this->extractStreetNames($coordinates);
    
    // Build streets list as a string
    $streetsList = "";
    foreach ($streets as $index => $street) {
        $streetsList .= ($index + 1) . ". " . $street . "\n";
    }
    
    // Build prompt for AI
    $prompt = "Tu es un assistant pour parents d'enfants autistes.
Analyse cet itinéraire rue par rue.

Heure actuelle: {$currentHour}h
Événement: {$eventTitle}

Rues à analyser:
{$streetsList}

Pour chaque rue, donne:
- Niveau sonore (calme/moyen/bruyant)
- Risque de foule (faible/moyen/élevé)
-Recommandation (conseil sur la rue de 80 characteres non moins)

Puis donne:
1. Un score global de calme (0-100)
2. Une recommandation générale 80 characteres
3. Un conseil sur l'heure de départ
4. Des suggestions d'alternatives si certaines rues sont bruyantes

Réponds en JSON uniquement avec cette structure:
{
    \"streets\": [
        {\"name\": \"...\", \"noise\": \"...\", \"crowd\": \"...\", \"recommendation\": \"...\"}
    ],
    \"global_score\": 0,
    \"recommendation\": \"...\",
    \"best_departure_time\": \"...\",
    \"alternatives\": \"...\"
}";

    $aiResponse = $groqService->generateRouteAnalysis($prompt);
    
    $analysis = json_decode($aiResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $analysis = ['error' => 'Invalid JSON response from AI', 'raw' => $aiResponse];
    }
    
    return $this->json([
        'success' => true,
        'analysis' => $analysis,
        'streets' => $streets
    ]);
}
private function geocodeLieu(string $lieu): ?array
{
    static $cache = [];
    
    if (isset($cache[$lieu])) {
        return $cache[$lieu];
    }
    
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($lieu . ", Tunisie") . "&format=json&limit=1";
    
    // LOG : Affiche l'URL appelée
    error_log("🔍 Geocoding: " . $url);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AutiCareApp/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    
    $response = curl_exec($ch);
    
   
    if (curl_error($ch)) {
        error_log("❌ Curl error: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($response) {
        error_log("📡 Response: " . substr($response, 0, 200));
        $data = json_decode($response, true);
        if (!empty($data)) {
            $cache[$lieu] = [
                'lat' => (float)$data[0]['lat'],
                'lng' => (float)$data[0]['lon']
            ];
            error_log("✅ Geocoded {$lieu} -> " . $cache[$lieu]['lat'] . ", " . $cache[$lieu]['lng']);
            return $cache[$lieu];
        }
    }
    
    error_log("❌ Failed to geocode: {$lieu}");
    $cache[$lieu] = null;
    return null;
}
#[Route('/api/map/events', name: 'api_map_events', methods: ['GET'])]
public function getMapEvents(EventRepository $eventRepository, EventAIPredictor $predictor): JsonResponse
{
    $events = $eventRepository->findAll();
    
    $data = [];
    foreach ($events as $event) {
        $coords = $this->geocodeLieu($event->getLieu());
        $mlScore = $predictor->predictAutismScore($event);
        
        $data[] = [
            'id' => $event->getIdEvent(),
            'title' => $event->getTitre(),
            'type' => $event->getTypeEvent(),
            'description' => $event->getDescription(),
            'lieu' => $event->getLieu(),
            'latitude' => $coords['lat'] ?? 36.8065,
            'longitude' => $coords['lng'] ?? 10.1815,
            'image' => $event->getImage(),
            'heureDebut' => $event->getHeureDebut()?->format('H:i'),
            'maxParticipant' => $event->getMaxParticipant(),
            'status' => $event->getStatus(),
            'url' => $this->generateUrl('app_event_show', ['id' => $event->getIdEvent()]),
            'mlScore' => round($mlScore, 1)
        ];
    }
    
    return $this->json(['success' => true, 'events' => $data]);
}
#[Route('/event/{id}/calendar.ics', name: 'event_calendar_ics', methods: ['GET'])]
public function generateCalendarIcs(int $id, EventRepository $eventRepository): Response
{
    $event = $eventRepository->find($id);
    if (!$event) {
        throw $this->createNotFoundException('Événement non trouvé');
    }
    
    $icsContent = $this->generateIcsContent($event);
    
    $response = new Response($icsContent);
    $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
    $response->headers->set('Content-Disposition', 'inline; filename="event-' . $event->getIdEvent() . '.ics"');
    
    return $response;
}

private function generateIcsContent(Event $event): string
{
    $dateDebut = $event->getDateDebut();
    $heureDebut = $event->getHeureDebut();
    
    $startDateTime = new \DateTime(
        $dateDebut->format('Y-m-d') . ' ' . $heureDebut->format('H:i:s')
    );
    
    $endDateTime = clone $startDateTime;
    $endDateTime->modify('+2 hours');
    
    $uid = uniqid() . '@auticare.com';
    
    $ics = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//AutiCare//Event Calendar//EN\r\n";
    $ics .= "CALSCALE:GREGORIAN\r\n";
    $ics .= "METHOD:PUBLISH\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:" . $uid . "\r\n";
    $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $ics .= "DTSTART:" . $startDateTime->format('Ymd\THis') . "\r\n";
    $ics .= "DTEND:" . $endDateTime->format('Ymd\THis') . "\r\n";
    $ics .= "SUMMARY:" . $this->escapeIcsText($event->getTitre()) . "\r\n";
    $ics .= "DESCRIPTION:" . $this->escapeIcsText($event->getDescription()) . "\r\n";
    $ics .= "LOCATION:" . $this->escapeIcsText($event->getLieu()) . "\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";
    
    return $ics;
}

private function escapeIcsText(string $text): string
{
    $text = str_replace([',', ';', '\\', "\n"], ['\\,', '\\;', '\\\\', '\\n'], $text);
    return $text;
}

#[Route('/api/ai/compare-routes', name: 'api_ai_compare_routes', methods: ['POST'])]
public function compareRoutes(Request $request, GroqService $groqService): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $fastCoordinates = $data['fastCoordinates'] ?? [];
    $calmCoordinates = $data['calmCoordinates'] ?? [];
    $eventTitle = $data['eventTitle'] ?? '';
    $currentHour = date('H');
    
    // Extraire les noms des rues pour les deux itinéraires
    $fastStreets = $this->extractStreetNames($fastCoordinates);
    $calmStreets = $this->extractStreetNames($calmCoordinates);
    
    // Construire la liste des rues
    $fastStreetsList = "";
    foreach ($fastStreets as $index => $street) {
        $fastStreetsList .= ($index + 1) . ". " . $street . "\n";
    }
    
    $calmStreetsList = "";
    foreach ($calmStreets as $index => $street) {
        $calmStreetsList .= ($index + 1) . ". " . $street . "\n";
    }
    
    // Prompt pour l'IA
    $prompt = "Compare ces deux itinéraires pour un enfant autiste.

Heure: {$currentHour}h
Événement: {$eventTitle}

🚗 ITINÉRAIRE RAPIDE (voiture):
Rues:
{$fastStreetsList}

🌿 ITINÉRAIRE CALME (voiture - évite autoroutes):
Rues:
{$calmStreetsList}

Réponds UNIQUEMENT en JSON avec cette structure:
{
    \"fast\": {
        \"noise\": \"faible/moyen/élevé\",
        \"crowd\": \"faible/moyen/élevé\",
        \"calm_score\": 0,
        \"pros\": \"avantages\",
        \"cons\": \"inconvénients\"
    },
    \"calm\": {
        \"noise\": \"faible/moyen/élevé\",
        \"crowd\": \"faible/moyen/élevé\",
        \"calm_score\": 0,
        \"pros\": \"avantages\",
        \"cons\": \"inconvénients\"
    },
    \"verdict\": \"recommandation finale courte\",
    \"tip\": \"conseil pour le trajet\"
}";

    $aiResponse = $groqService->generateRouteAnalysis($prompt);
    $analysis = json_decode($aiResponse, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($analysis['fast'])) {
        // Fallback
        $analysis = [
            'fast' => ['noise' => 'moyen', 'crowd' => 'moyen', 'calm_score' => 50, 'pros' => 'Plus rapide', 'cons' => 'Plus bruyant'],
            'calm' => ['noise' => 'faible', 'crowd' => 'faible', 'calm_score' => 80, 'pros' => 'Plus calme', 'cons' => 'Plus long'],
            'verdict' => 'Le trajet calme est recommandé',
            'tip' => 'Prépare ton enfant avant le départ'
        ];
    }
    
    return $this->json([
        'success' => true,
        'analysis' => $analysis,
        'fast_streets' => $fastStreets,
        'calm_streets' => $calmStreets
    ]);
}
#[Route('/api/ai/extract-streets', name: 'api_ai_extract_streets', methods: ['POST'])]
public function extractStreets(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $coordinates = $data['coordinates'] ?? [];
    
    if (empty($coordinates)) {
        return $this->json(['error' => 'No coordinates'], 400);
    }
    
    $streets = $this->extractStreetNames($coordinates);
    
    return $this->json([
        'success' => true,
        'streets' => $streets
    ]);
}
#[Route('/api/ai/analyze-streets', name: 'api_ai_analyze_streets', methods: ['POST'])]
public function analyzeStreetsWithAI(Request $request, GroqService $groqService): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $streets = $data['streets'] ?? [];
    $eventTitle = $data['eventTitle'] ?? '';
    $currentHour = date('H');
    
    if (empty($streets)) {
        return $this->json(['error' => 'No streets provided'], 400);
    }
    
    // Construire la liste des rues
    $streetsList = "";
    foreach ($streets as $index => $street) {
        $streetsList .= ($index + 1) . ". " . $street . "\n";
    }
    
    // Prompt IA pour analyser rue par rue
    $prompt = "Analyse cet itinéraire rue par rue pour un enfant autiste.

Heure actuelle: {$currentHour}h
Événement: {$eventTitle}

Rues à analyser:
{$streetsList}

Pour chaque rue, donne:
- Niveau sonore (calme/moyen/bruyant)
- Risque de foule (faible/moyen/élevé)
- Si la rue est bruyante, propose une alternative (nom d'une rue voisine plus calme)

Retourne UNIQUEMENT ce JSON, rien d'autre:
{
    \"streets\": [
        {
            \"name\": \"nom de la rue\",
            \"noise\": \"calme/moyen/bruyant\",
            \"crowd\": \"faible/moyen/élevé\",
            \"alternative\": \"nom rue alternative (si bruyant, sinon null)\"
        }
    ],
    \"global_score\": 0,
    \"global_rating\": \"très calme/calme/moyen/bruyant/très bruyant\",
    \"verdict\": \"recommandation générale courte\",
    \"tip\": \"conseil pour le trajet\"
}";

    $aiResponse = $groqService->generateRouteAnalysis($prompt);
    $analysis = json_decode($aiResponse, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($analysis['streets'])) {
        // Fallback
        $fallbackStreets = [];
        foreach ($streets as $street) {
            $fallbackStreets[] = [
                'name' => $street,
                'noise' => 'moyen',
                'crowd' => 'moyen',
                'alternative' => null
            ];
        }
        $analysis = [
            'streets' => $fallbackStreets,
            'global_score' => 50,
            'global_rating' => 'moyen',
            'verdict' => 'Trajet standard, prévoir des écouteurs',
            'tip' => 'Faire des pauses si nécessaire'
        ];
    }
    
    return $this->json([
        'success' => true,
        'analysis' => $analysis
    ]);
}
}
