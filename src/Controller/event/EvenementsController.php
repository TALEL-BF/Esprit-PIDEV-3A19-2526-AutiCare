<?php

namespace App\Controller\event;

use App\Entity\Event;
use App\Entity\Sponsor;
use App\Repository\EventRepository;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use TCPDF;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Services\AiPlanningService;
use App\Services\BadWordsService\GroqService; 
use App\Services\EventAIPredictor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EvenementsController extends AbstractController
{
    private EventRepository $eventRepository;
    private SponsorRepository $sponsorRepository;

    public function __construct(
        EventRepository $eventRepository,
        SponsorRepository $sponsorRepository
    ) {
        $this->eventRepository = $eventRepository;
        $this->sponsorRepository = $sponsorRepository;
    }

    #[Route('/admin/evenements', name: 'admin_evenements')]
    public function index(Request $request, EntityManagerInterface $em, EventAIPredictor $predictor): Response
    {
        $events = $em->getRepository(Event::class)->findAll();
        $sponsors = $em->getRepository(Sponsor::class)->findAll();
        
        
        $predictor->loadModel();
        
        $totalEvents = count($events);
        $today = new \DateTime();
        $upcomingEvents = 0;
        $totalParticipants = 0;
        
        $typeStats = [];
        $scoreStats = [
            'excellent' => 0,  // >=80
            'good' => 0,       // 70-79
            'moderate' => 0,   // 55-69
            'low' => 0,        // 40-54
            'poor' => 0        // <40
        ];
        
        foreach ($events as $event) {
            $type = $event->getTypeEvent();
            if (!isset($typeStats[$type])) {
                $typeStats[$type] = 0;
            }
            $typeStats[$type]++;
            
            if ($event->getDateDebut() >= $today) {
                $upcomingEvents++;
            }
            
            
            $score = $predictor->predictAutismScore($event);
            if ($score >= 80) $scoreStats['excellent']++;
            elseif ($score >= 70) $scoreStats['good']++;
            elseif ($score >= 55) $scoreStats['moderate']++;
            elseif ($score >= 40) $scoreStats['low']++;
            else $scoreStats['poor']++;
        }
        
        $directEditId = $request->query->get('edit');
        
        return $this->render('admin/pages/event/evenements.html.twig', [
            'events' => $events,
            'sponsors' => $sponsors,
            'totalEvents' => $totalEvents,
            'upcomingEvents' => $upcomingEvents,
            'totalParticipants' => $totalParticipants,
            'typeStats' => $typeStats,
            'scoreStats' => $scoreStats,
            'directEditId' => $directEditId,
            'modelTrained' => $predictor->isTrained()
        ]);
    }
    
    
    #[Route('/admin/evenements/ai-score/{id}', name: 'admin_evenements_ai_score', methods: ['GET'])]
    public function getAiScore(int $id, EntityManagerInterface $em, EventAIPredictor $predictor): JsonResponse
    {
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'error' => 'Event not found'], 404);
        }
        
        $score = $predictor->predictAutismScore($event);
        
        return $this->json([
            'success' => true,
            'score' => round($score, 1),
            'level' => $this->getScoreLevel($score),
            'class' => $this->getScoreClass($score),
            'icon' => $this->getScoreIcon($score)
        ]);
    }
    
    
    #[Route('/admin/evenements/train-model', name: 'admin_evenements_train_model', methods: ['POST'])]
    public function trainModel(EntityManagerInterface $em, EventAIPredictor $predictor): JsonResponse
    {
        $events = $em->getRepository(Event::class)->findAll();
        
        if (empty($events)) {
            return $this->json(['success' => false, 'message' => 'No events to train on'], 400);
        }
        
        try {
            $startTime = microtime(true);
            $predictor->train($events);
            $trainingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return $this->json([
                'success' => true,
                'message' => 'Model trained on ' . count($events) . ' events',
                'training_time_ms' => $trainingTime
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/admin/evenements/add', name: 'admin_evenements_add', methods: ['POST'])]
    public function addEvent(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        ValidatorInterface $validator,
        EventAIPredictor $predictor
    ): JsonResponse
    {
        error_log('=== ADD EVENT ===');
        error_log('POST: ' . print_r($request->request->all(), true));
        error_log('FILES: ' . print_r($request->files->all(), true));
        
        $event = new Event();
        
        $event->setTitre(trim($request->request->get('eventTitle', '')));
        $event->setDescription(trim($request->request->get('eventDescription', '')));
        $event->setTypeEvent($request->request->get('eventType', ''));
        $event->setLieu(trim($request->request->get('eventLocation', '')));
        $event->setMaxParticipant((int)$request->request->get('eventCapacity', 0));
        
        $startDate = $request->request->get('eventStartDate');
        $startTime = $request->request->get('eventStartTime');
        
        if ($startDate) {
            $event->setDateDebut(new \DateTime($startDate));
        }
        
        if ($startTime) {
            $timeOnly = \DateTime::createFromFormat('H:i', $startTime);
            if ($timeOnly) {
                $event->setHeureDebut($timeOnly);
            }
        }
        
        $endDate = $request->request->get('eventEndDate');
        $endTime = $request->request->get('eventEndTime');
        
        if ($endDate && !empty($endDate)) {
            $event->setDateFin(new \DateTime($endDate));
        }
        
        if ($endTime && !empty($endTime)) {
            $timeOnly = \DateTime::createFromFormat('H:i', $endTime);
            if ($timeOnly) {
                $event->setHeureFin($timeOnly);
            }
        }
        
        $status = $request->request->get('eventStatus', 'planifie');
        $event->setStatus($status);
        
        $imageFile = $request->files->get('eventImage');
        
        error_log('Image file: ' . ($imageFile ? $imageFile->getClientOriginalName() : 'NULL'));
        
        if (!$imageFile) {
            return $this->json(['success' => false, 'errors' => ['Une image est obligatoire pour l\'événement']]);
        }
        
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->getClientOriginalExtension();
        
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
        error_log('Upload directory: ' . $uploadDir);
        
        try {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                error_log('Created directory');
            }
            
            $imageFile->move($uploadDir, $newFilename);
            error_log('File moved successfully: ' . $newFilename);
            
            $event->setImage('uploads/events/' . $newFilename);
            error_log('Image path stored: ' . $event->getImage());
            
        } catch (FileException $e) {
            error_log('Upload error: ' . $e->getMessage());
            return $this->json(['success' => false, 'errors' => ['Erreur lors de l\'upload: ' . $e->getMessage()]]);
        }
        
        $errors = $validator->validate($event);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['success' => false, 'errors' => $errorMessages]);
        }
        
        $em->persist($event);
        $em->flush();
        
       
        $allEvents = $em->getRepository(Event::class)->findAll();
        $predictor->train($allEvents);
        
        error_log('Event saved with ID: ' . $event->getIdEvent());
        
        return $this->json([
            'success' => true, 
            'message' => 'Événement ajouté avec succès !',
            'imagePath' => '/' . $event->getImage(),
            'aiScore' => round($predictor->predictAutismScore($event), 1)
        ]);
    }
    
    #[Route('/admin/evenements/get/{id}', name: 'admin_evenements_get', methods: ['GET'])]
    public function getEvent(int $id, EntityManagerInterface $em): JsonResponse
    {
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Événement non trouvé'], 404);
        }
        
        $imagePath = $event->getImage();
        $fullImagePath = null;
        
        if ($imagePath) {
            if (str_starts_with($imagePath, 'uploads/')) {
                $fullImagePath = '/' . $imagePath;
            } else {
                $fullImagePath = '/uploads/events/' . $imagePath;
            }
        }
        
        return $this->json([
            'success' => true,
            'event' => [
                'idEvent' => $event->getIdEvent(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'typeEvent' => $event->getTypeEvent(),
                'lieu' => $event->getLieu(),
                'maxParticipant' => $event->getMaxParticipant(),
                'dateDebut' => $event->getDateDebut()?->format('Y-m-d'),
                'heureDebut' => $event->getHeureDebut()?->format('H:i'),
                'dateFin' => $event->getDateFin()?->format('Y-m-d'),
                'heureFin' => $event->getHeureFin()?->format('H:i'),
                'status' => $event->getStatus(),
                'image' => $fullImagePath,
            ]
        ]);
    }

    #[Route('/admin/evenements/update', name: 'admin_evenements_update', methods: ['POST'])]
    public function updateEvent(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        ValidatorInterface $validator,
        EventAIPredictor $predictor
    ): JsonResponse
    {
        $id = $request->request->get('eventId');
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'errors' => ['Événement non trouvé']]);
        }
        
        $event->setTitre(trim($request->request->get('eventTitle', '')));
        $event->setDescription(trim($request->request->get('eventDescription', '')));
        $event->setTypeEvent($request->request->get('eventType', ''));
        $event->setLieu(trim($request->request->get('eventLocation', '')));
        $event->setMaxParticipant((int)$request->request->get('eventCapacity', 0));
        
        $startDate = $request->request->get('eventStartDate');
        $startTime = $request->request->get('eventStartTime');
        
        if ($startDate) {
            $event->setDateDebut(new \DateTime($startDate));
        }
        
        if ($startTime) {
            $timeOnly = \DateTime::createFromFormat('H:i', $startTime);
            if ($timeOnly) {
                $event->setHeureDebut($timeOnly);
            }
        }
        
        $endDate = $request->request->get('eventEndDate');
        $endTime = $request->request->get('eventEndTime');
        
        if ($endDate && !empty($endDate)) {
            $event->setDateFin(new \DateTime($endDate));
        } else {
            $event->setDateFin(null);
        }
        
        if ($endTime && !empty($endTime)) {
            $timeOnly = \DateTime::createFromFormat('H:i', $endTime);
            if ($timeOnly) {
                $event->setHeureFin($timeOnly);
            }
        } else {
            $event->setHeureFin(null);
        }
        
        $status = $request->request->get('eventStatus', 'planifie');
        $event->setStatus($status);
        
        $imageFile = $request->files->get('eventImage');
        
        if ($imageFile && $imageFile->getSize() > 0) {
            $oldImage = $event->getImage();
            if ($oldImage) {
                $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/' . $oldImage;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->getClientOriginalExtension();
            
            try {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $imageFile->move($uploadDir, $newFilename);
                $event->setImage('uploads/events/' . $newFilename);
            } catch (FileException $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de l\'upload: ' . $e->getMessage()]]);
            }
        }
        
        $errors = $validator->validate($event);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['success' => false, 'errors' => $errorMessages]);
        }
        
        $em->flush();
        
      
        $allEvents = $em->getRepository(Event::class)->findAll();
        $predictor->train($allEvents);
        
        return $this->json([
            'success' => true, 
            'message' => 'Événement modifié avec succès !',
            'imagePath' => '/' . $event->getImage(),
            'aiScore' => round($predictor->predictAutismScore($event), 1)
        ]);
    }

    #[Route('/admin/evenements/delete', name: 'admin_evenements_delete', methods: ['DELETE'])]
    public function deleteEvent(Request $request, EntityManagerInterface $em, EventAIPredictor $predictor): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? $request->request->get('id');
        
        if (!$id) {
            return $this->json(['success' => false, 'error' => 'ID manquant'], 400);
        }
        
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'error' => 'Événement non trouvé'], 404);
        }
        
        try {
            if ($event->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/' . $event->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $em->remove($event);
            $em->flush();
            
           
            $allEvents = $em->getRepository(Event::class)->findAll();
            if (!empty($allEvents)) {
                $predictor->train($allEvents);
            }
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

  #[Route('/admin/evenements/search', name: 'admin_evenements_search', methods: ['POST'])]
public function searchEvents(Request $request, EventRepository $eventRepository): JsonResponse
{
    $searchTerm = $request->request->get('search', '');
    $type = $request->request->get('type', 'tous');
    $status = $request->request->get('status', 'tous');
    $period = $request->request->get('period', 'toutes');
    $sortBy = $request->request->get('sortBy', 'date_desc');
    
    
    $qb = $eventRepository->createFilteredQueryBuilder(
        searchTerm: $searchTerm,
        type: $type === 'tous' ? null : $type,
        status: $status === 'tous' ? null : $status,
        period: $period === 'toutes' ? null : $period,
        sortBy: $sortBy
    );
    
    $events = $qb->getQuery()->getResult();
    
    // ========== FORMATAGE DES RÉSULTATS ==========
    $data = [];
    foreach ($events as $event) {
        $data[] = [
            'idEvent' => $event->getIdEvent(),
            'titre' => $event->getTitre(),
            'description' => $event->getDescription(),
            'typeEvent' => $event->getTypeEvent(),
            'lieu' => $event->getLieu(),
            'maxParticipant' => $event->getMaxParticipant(),
            'dateDebut' => $event->getDateDebut()?->format('Y-m-d'),
            'heureDebut' => $event->getHeureDebut()?->format('H:i'),
            'dateFin' => $event->getDateFin()?->format('Y-m-d'),
            'heureFin' => $event->getHeureFin()?->format('H:i'),
            'status' => $event->getStatus(),
            'image' => $event->getImage(),
            'imagePath' => $event->getImage() ? '/' . $event->getImage() : null,
            'sponsorCount' => $event->getSponsors()->count(),
            'inscrits' => 0
        ];
    }
    
    return $this->json([
        'success' => true,
        'events' => $data,
        'total' => count($data)
    ]);
}
  #[Route('/admin/evenements/dashboard', name: 'admin_evenements_dashboard')]
public function dashboard(EntityManagerInterface $em): Response
{
    $eventRepository = $em->getRepository(Event::class);
    $sponsorRepository = $em->getRepository(Sponsor::class);
    
    $events = $eventRepository->findAll();
    $sponsors = $sponsorRepository->findAll();
    
    $totalEvents = count($events);
    $totalSponsors = count($sponsors);
    
    // Budget total des sponsors
    $totalBudget = 0;
    foreach ($sponsors as $sponsor) {
        $totalBudget += $sponsor->getMontant() ?? 0;
    }
    
    // Événements à venir
    $today = new \DateTime();
    $upcomingEvents = 0;
    foreach ($events as $event) {
        if ($event->getDateDebut() && $event->getDateDebut() >= $today) {
            $upcomingEvents++;
        }
    }
    
    // ========== STATS PAR MOIS ==========
    $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
    $eventsByMonth = [];
    
    for ($i = 0; $i < 12; $i++) {
        $monthNum = $i + 1;
        $monthShort = $monthNames[$i];
        
        $eventCount = 0;
        foreach ($events as $event) {
            $dateDebut = $event->getDateDebut();
            if ($dateDebut && (int)$dateDebut->format('n') == $monthNum) {
                $eventCount++;
            }
        }
        $eventsByMonth[$monthShort] = $eventCount;
    }
    
    // ========== STATS PAR TYPE ==========
    $typeStats = [];
    foreach ($events as $event) {
        $type = $event->getTypeEvent();
        if (!isset($typeStats[$type])) {
            $typeStats[$type] = 0;
        }
        $typeStats[$type]++;
    }
    
    // ========== STATS RÉCENTES ==========
    $startOfMonth = new \DateTime('first day of this month 00:00:00');
    $endOfMonth = new \DateTime('last day of this month 23:59:59');
    $eventsThisMonth = 0;
    foreach ($events as $event) {
        $dateDebut = $event->getDateDebut();
        if ($dateDebut && $dateDebut >= $startOfMonth && $dateDebut <= $endOfMonth) {
            $eventsThisMonth++;
        }
    }
    
    $newSponsorsThisMonth = 0;
    $avgBudgetPerEvent = $totalEvents > 0 ? round(($totalBudget / $totalEvents) / 1000, 1) : 0;
    $participationRate = 76;
    
    // Taux de croissance
    $lastYear = (new \DateTime())->modify('-1 year');
    $eventsLastYear = 0;
    foreach ($events as $event) {
        $dateDebut = $event->getDateDebut();
        if ($dateDebut && $dateDebut >= $lastYear && $dateDebut <= $today) {
            $eventsLastYear++;
        }
    }
    $growthRate = $eventsLastYear > 0 ? round(($totalEvents - $eventsLastYear) / $eventsLastYear * 100) : 0;
    
    // ========== DONNÉES RADAR ==========
    $engagement = $totalEvents > 0 ? min(100, round(($upcomingEvents / $totalEvents) * 100)) : 50;
    $budgetPerf = $totalBudget > 0 ? min(100, round($totalBudget / 10000)) : 40;
    $visibilite = $totalSponsors > 0 ? min(100, round(($totalSponsors / $totalEvents) * 20)) : 50;
    $satisfaction = 78;
    $croissance = $growthRate;
    
    $radarData = [$engagement, $budgetPerf, $participationRate, $visibilite, $satisfaction, $croissance];
    
    // ========== DONNÉES BUBBLE CHART ==========
    $bubbleData = [];
    $sampleEvents = array_slice($events, 0, 8);
    foreach ($sampleEvents as $event) {
        $budgetEvent = 0;
        foreach ($event->getSponsors() as $sponsor) {
            $budgetEvent += $sponsor->getMontant() ?? 0;
        }
        $bubbleData[] = [
            'x' => round($budgetEvent / 1000, 1),
            'y' => rand(40, 95),
            'r' => 15 + min(30, ($event->getMaxParticipant() / 100))
        ];
    }
    
    // ✅ RENVOI AVEC LA VARIABLE 'events' AJOUTÉE
    return $this->render('admin/pages/event/evenements_dashboard.html.twig', [
        'events' => $events,  // ← AJOUTÉE !!!
        'totalEvents' => $totalEvents,
        'upcomingEvents' => $upcomingEvents,
        'totalSponsors' => $totalSponsors,
        'totalBudget' => round($totalBudget / 1000, 1),
        'eventsByMonth' => $eventsByMonth,
        'typeStats' => $typeStats,
        'eventsThisMonth' => $eventsThisMonth,
        'newSponsorsThisMonth' => $newSponsorsThisMonth,
        'avgBudgetPerEvent' => $avgBudgetPerEvent,
        'participationRate' => $participationRate,
        'growthRate' => $growthRate,
        'radarData' => $radarData,
        'bubbleData' => $bubbleData
    ]);
}
    #[Route('/admin/evenements/predictions', name: 'admin_evenements_predictions')]
    public function predictions(EventAIPredictor $predictor, EntityManagerInterface $em): Response
    {
        $events = $em->getRepository(Event::class)->findAll();
        
        $predictor->train($events);
        
        $predictions = [];
        $totalScore = 0;
        $bestScore = 0;
        $bestEventName = '';
        $goodEventsCount = 0;
        
        foreach ($events as $event) {
            $score = $predictor->predictAutismScore($event);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEventName = $event->getTitre();
            }
            
            if ($score >= 70) {
                $goodEventsCount++;
            }
            
            $predictions[] = [
                'event' => $event,
                'score' => round($score, 1),
                'level' => $this->getScoreLevel($score)
            ];
            $totalScore += $score;
        }
        
        usort($predictions, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $this->render('admin/pages/event/event_predictions.html.twig', [
            'predictions' => $predictions,
            'averageScore' => count($events) > 0 ? round($totalScore / count($events), 1) : 0,
            'totalEvents' => count($events),
            'bestScore' => round($bestScore, 1),
            'bestEventName' => $bestEventName,
            'goodEventsCount' => $goodEventsCount
        ]);
    }

    #[Route('/admin/dashboard/recent-events', name: 'admin_dashboard_recent_events', methods: ['GET'])]
    public function getRecentEvents(EntityManagerInterface $em): JsonResponse
    {
        $events = $em->getRepository(Event::class)->findBy([], ['dateDebut' => 'DESC'], 5);
        
        $data = [];
        foreach ($events as $event) {
            $totalBudget = 0;
            foreach ($event->getSponsors() as $sponsor) {
                $totalBudget += $sponsor->getMontant() ?? 0;
            }
            
            $data[] = [
                'idEvent' => $event->getIdEvent(),
                'titre' => $event->getTitre(),
                'dateDebut' => $event->getDateDebut()?->format('Y-m-d'),
                'lieu' => $event->getLieu(),
                'sponsorCount' => $event->getSponsors()->count(),
                'budgetTotal' => $totalBudget,
            ];
        }
        
        $allEvents = $em->getRepository(Event::class)->findAll();
        $allSponsors = $em->getRepository(Sponsor::class)->findAll();
        
        $totalEvents = count($allEvents);
        
        $today = new \DateTime();
        $upcomingEvents = 0;
        foreach ($allEvents as $event) {
            if ($event->getDateDebut() && $event->getDateDebut() >= $today) {
                $upcomingEvents++;
            }
        }
        
        $totalSponsors = count($allSponsors);
        
        $totalBudget = 0;
        foreach ($allSponsors as $sponsor) {
            $totalBudget += $sponsor->getMontant() ?? 0;
        }
        
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');
        $eventsThisMonth = 0;
        foreach ($allEvents as $event) {
            $dateDebut = $event->getDateDebut();
            if ($dateDebut && $dateDebut >= $startOfMonth && $dateDebut <= $endOfMonth) {
                $eventsThisMonth++;
            }
        }
        
        $newSponsorsThisMonth = 0;
        $avgBudgetPerEvent = $totalEvents > 0 ? round(($totalBudget / $totalEvents) / 1000, 1) : 0;
        $participationRate = 76;
        $growthRate = 68;
        
        return $this->json([
            'success' => true,
            'events' => $data,
            'stats' => [
                'totalEvents' => $totalEvents,
                'upcomingEvents' => $upcomingEvents,
                'totalSponsors' => $totalSponsors,
                'totalBudget' => round($totalBudget / 1000, 1),
                'eventsThisMonth' => $eventsThisMonth,
                'newSponsorsThisMonth' => $newSponsorsThisMonth,
                'avgBudgetPerEvent' => $avgBudgetPerEvent,
                'participationRate' => $participationRate,
                'growthRate' => $growthRate,
            ]
        ]);
    }

    #[Route('/admin/evenements/apply-suggestion', name: 'admin_evenements_apply_suggestion', methods: ['POST'])]
    public function applySuggestion(Request $request, EntityManagerInterface $em, EventAIPredictor $predictor): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $event = $em->getRepository(Event::class)->find($data['eventId']);
        
        if (!$event) {
            return $this->json(['success' => false, 'error' => 'Event not found']);
        }
        
        switch($data['field']) {
            case 'capacite':
                $event->setMaxParticipant((int)$data['value']);
                break;
            case 'type':
                $event->setTypeEvent($data['value']);
                break;
            case 'description':
                $currentDesc = $event->getDescription();
                if (!str_contains($currentDesc, $data['value'])) {
                    $event->setDescription($currentDesc . ' ' . $data['value']);
                }
                break;
            case 'titre':
                $event->setTitre($data['value']);
                break;
        }
        
        $em->flush();
        
        $allEvents = $em->getRepository(Event::class)->findAll();
        $predictor->train($allEvents);
        $newScore = $predictor->predictAutismScore($event);
        
        return $this->json([
            'success' => true,
            'newScore' => round($newScore, 1)
        ]);
    }

    #[Route('/admin/evenements/ai-suggestions', name: 'admin_evenements_ai_suggestions', methods: ['POST'])]
    public function aiSuggestions(Request $request, GroqService $groqService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $eventData = [
            'title' => $data['title'] ?? '',
            'type' => $data['type'] ?? '',
            'description' => $data['description'] ?? '',
            'capacity' => $data['capacity'] ?? 0,
            'currentScore' => $data['currentScore'] ?? 50
        ];
        
        $suggestions = $groqService->generateEventImprovementSuggestions($eventData);
        
        return $this->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    #[Route('/admin/evenements/add-event-type', name: 'admin_evenements_add_event_type', methods: ['POST'])]
    public function addEventType(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newType = trim($data['type']);
        
        if (empty($newType)) {
            return $this->json(['success' => false, 'error' => 'Type invalide']);
        }
        
        return $this->json(['success' => true, 'type' => $newType]);
    }
    
    // ========== PRIVATE HELPER METHODS ==========
    
    private function getScoreLevel(float $score): string
    {
        if ($score >= 80) return 'Excellent 🌟';
        if ($score >= 70) return 'Très adapté ✅';
        if ($score >= 55) return 'Adapté 👍';
        if ($score >= 40) return 'Modérément adapté ⚠️';
        if ($score >= 25) return 'Peu adapté ❌';
        return 'Déconseillé 🚫';
    }
    
    private function getScoreClass(float $score): string
    {
        if ($score >= 70) return 'success';
        if ($score >= 40) return 'warning';
        return 'danger';
    }
    
    private function getScoreIcon(float $score): string
    {
        if ($score >= 70) return '🌟';
        if ($score >= 40) return '⚠️';
        return '❌';
    }
}