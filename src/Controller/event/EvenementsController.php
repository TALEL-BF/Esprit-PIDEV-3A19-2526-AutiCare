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
use App\Service\AiPlanningService;
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
    public function index(EntityManagerInterface $em): Response
    {
        $events = $em->getRepository(Event::class)->findAll();
        $sponsors = $em->getRepository(Sponsor::class)->findAll();
        
        $totalEvents = count($events);
        $today = new \DateTime();
        $upcomingEvents = 0;
        $totalParticipants = 0;
        
        $typeStats = [];
        foreach ($events as $event) {
            $type = $event->getTypeEvent();
            if (!isset($typeStats[$type])) {
                $typeStats[$type] = 0;
            }
            $typeStats[$type]++;
            
            if ($event->getDateDebut() >= $today) {
                $upcomingEvents++;
            }
        }
        
        return $this->render('admin/pages/events/evenements.html.twig', [
            'events' => $events,
            'sponsors' => $sponsors,
            'totalEvents' => $totalEvents,
            'upcomingEvents' => $upcomingEvents,
            'totalParticipants' => $totalParticipants,
            'typeStats' => $typeStats,
        ]);
    }

    #[Route('/admin/evenements/add', name: 'admin_evenements_add', methods: ['POST'])]
public function addEvent(
    Request $request, 
    EntityManagerInterface $em, 
    SluggerInterface $slugger,
    ValidatorInterface $validator
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
    error_log('Directory exists? ' . (file_exists($uploadDir) ? 'YES' : 'NO'));
    
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
    
    error_log('Event saved with ID: ' . $event->getIdEvent());
    
    return $this->json([
        'success' => true, 
        'message' => 'Événement ajouté avec succès !',
        'imagePath' => '/' . $event->getImage()
    ]);
}
    #[Route('/admin/evenements/get/{id}', name: 'admin_evenements_get', methods: ['GET'])]
    public function getEvent(int $id, EntityManagerInterface $em): JsonResponse
    {
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Événement non trouvé'], 404);
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
                'image' => $event->getImage() ? '/uploads/events/' . $event->getImage() : null,
            ]
        ]);
    }

    #[Route('/admin/evenements/update', name: 'admin_evenements_update', methods: ['POST'])]
    public function updateEvent(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        ValidatorInterface $validator
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
            // Delete old image
            if ($event->getImage()) {
                $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/events/' . $event->getImage();
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
                $event->setImage($newFilename);
            } catch (FileException $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de l\'upload de l\'image']]);
            }
        }
        
        // Validate entity
        $errors = $validator->validate($event);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['success' => false, 'errors' => $errorMessages]);
        }
        
        $em->flush();
        
        return $this->json(['success' => true, 'message' => 'Événement modifié avec succès !']);
    }

    #[Route('/admin/evenements/delete', name: 'admin_evenements_delete', methods: ['DELETE'])]
    public function deleteEvent(Request $request, EntityManagerInterface $em): JsonResponse
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
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/events/' . $event->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $em->remove($event);
            $em->flush();
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/admin/evenements/search', name: 'admin_evenements_search', methods: ['POST'])]
    public function searchEvents(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $searchTerm = $request->request->get('search', '');
        $type = $request->request->get('type', 'tous');
        $status = $request->request->get('status', 'tous');
        $period = $request->request->get('period', 'toutes');
        $sortBy = $request->request->get('sortBy', 'date_desc');
        
        $repository = $em->getRepository(Event::class);
        
        $qb = $repository->createQueryBuilder('e');
        
        if ($searchTerm) {
            $qb->andWhere('e.titre LIKE :search OR e.description LIKE :search OR e.lieu LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        if ($type && $type !== 'tous') {
            $qb->andWhere('e.typeEvent = :type')
               ->setParameter('type', $type);
        }
        
        if ($status && $status !== 'tous') {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $status);
        }
        
        $today = new \DateTime();
        if ($period === 'upcoming') {
            $qb->andWhere('e.dateDebut >= :today')
               ->setParameter('today', $today);
        } elseif ($period === 'past') {
            $qb->andWhere('e.dateDebut < :today')
               ->setParameter('today', $today);
        } elseif ($period === 'this_month') {
            $start = new \DateTime('first day of this month');
            $end = new \DateTime('last day of this month');
            $qb->andWhere('e.dateDebut BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        } elseif ($period === 'next_month') {
            $start = new \DateTime('first day of next month');
            $end = new \DateTime('last day of next month');
            $qb->andWhere('e.dateDebut BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }
        
        switch ($sortBy) {
            case 'date_asc':
                $qb->orderBy('e.dateDebut', 'ASC');
                break;
            case 'titre_asc':
                $qb->orderBy('e.titre', 'ASC');
                break;
            case 'titre_desc':
                $qb->orderBy('e.titre', 'DESC');
                break;
            case 'capacity_desc':
                $qb->orderBy('e.maxParticipant', 'DESC');
                break;
            default:
                $qb->orderBy('e.dateDebut', 'DESC');
        }
        
        $events = $qb->getQuery()->getResult();
        
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
        $events = $em->getRepository(Event::class)->findAll();
        
        $totalEvents = count($events);
        $today = new \DateTime();
        
        $upcomingEvents = 0;
        $pastEvents = 0;
        $eventsByMonth = [];
        $eventsByType = [];
        $eventsByStatus = [];
        
        foreach ($events as $event) {
            if ($event->getDateDebut() >= $today) {
                $upcomingEvents++;
            } else {
                $pastEvents++;
            }
            
            $type = $event->getTypeEvent();
            if (!isset($eventsByType[$type])) {
                $eventsByType[$type] = 0;
            }
            $eventsByType[$type]++;
            
            $status = $event->getStatus();
            if (!isset($eventsByStatus[$status])) {
                $eventsByStatus[$status] = 0;
            }
            $eventsByStatus[$status]++;
            
            $month = $event->getDateDebut()->format('F Y');
            if (!isset($eventsByMonth[$month])) {
                $eventsByMonth[$month] = 0;
            }
            $eventsByMonth[$month]++;
        }
        
        $recentEvents = $em->getRepository(Event::class)->findBy([], ['dateDebut' => 'DESC'], 5);
        $totalCapacity = array_sum(array_map(fn($e) => $e->getMaxParticipant(), $events));
        $averageFillRate = 0;
        
        return $this->render('admin/pages/events/evenements_dashboard.html.twig', [
            'totalEvents' => $totalEvents,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'totalCapacity' => $totalCapacity,
            'averageFillRate' => $averageFillRate,
            'eventsByType' => $eventsByType,
            'eventsByStatus' => $eventsByStatus,
            'eventsByMonth' => $eventsByMonth,
            'recentEvents' => $recentEvents,
        ]);
    }

    #[Route('/admin/evenements/{id}/planning', name: 'admin_evenements_planning_page', methods: ['GET'])]
    public function planningPage(int $id, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }
        
        return $this->render('admin/pages/events/event_planning.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/admin/evenements/{id}/planning/get', name: 'admin_evenements_planning_get', methods: ['GET'])]
    public function getPlanningAjax(int $id, EntityManagerInterface $em): JsonResponse
    {
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }
        
        $planning = $event->getPlanning();
        $planningData = $planning ? json_decode($planning, true) : ['creneaux' => []];
        
        return $this->json([
            'success' => true,
            'planning' => $planningData
        ]);
    }

    #[Route('/admin/evenements/{id}/planning/generate', name: 'admin_evenements_planning_generate', methods: ['POST'])]
    public function generatePlanningAjax(int $id, EntityManagerInterface $em, AiPlanningService $aiService): JsonResponse
    {
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }
        
        try {
            $planningData = $aiService->generatePlanning($event, $em);
            return $this->json([
                'success' => true,
                'planning' => $planningData
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/admin/evenements/{id}/planning/save', name: 'admin_evenements_planning_save', methods: ['POST'])]
    public function savePlanningAjax(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['planning'])) {
            $event->setPlanning(json_encode($data['planning'], JSON_UNESCAPED_UNICODE));
            $em->flush();
            return $this->json(['success' => true]);
        }
        
        return $this->json(['error' => 'Données invalides'], 400);
    }

    #[Route('/admin/evenements/export-pdf', name: 'admin_evenements_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EntityManagerInterface $em): Response
    {
        $searchTerm = $request->query->get('search', '');
        $type = $request->query->get('type', 'tous');
        $status = $request->query->get('status', 'tous');
        $period = $request->query->get('period', 'toutes');
        $sortBy = $request->query->get('sortBy', 'date_desc');
        
        $repository = $em->getRepository(Event::class);
        $qb = $repository->createQueryBuilder('e');
        
        if ($searchTerm) {
            $qb->andWhere('e.titre LIKE :search OR e.description LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        if ($type && $type !== 'tous') {
            $qb->andWhere('e.typeEvent = :type')->setParameter('type', $type);
        }
        
        if ($status && $status !== 'tous') {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }
        
        $today = new \DateTime();
        if ($period === 'upcoming') {
            $qb->andWhere('e.dateDebut >= :today')->setParameter('today', $today);
        } elseif ($period === 'past') {
            $qb->andWhere('e.dateDebut < :today')->setParameter('today', $today);
        }
        
        $qb->orderBy('e.dateDebut', 'DESC');
        $events = $qb->getQuery()->getResult();
        
        $totalEvents = count($events);
        $upcomingEvents = 0;
        foreach ($events as $event) {
            if ($event->getDateDebut() >= $today) $upcomingEvents++;
        }
        
        $sponsors = $em->getRepository(Sponsor::class)->findAll();
        $totalSponsors = count($sponsors);
        
        $totalBudget = 0;
        foreach ($sponsors as $sponsor) {
            $totalBudget += $sponsor->getMontant() ?? 0;
        }
        
        $html = $this->renderView('admin/pdf/evenements_pdf.html.twig', [
            'events' => $events,
            'totalEvents' => $totalEvents,
            'upcomingEvents' => $upcomingEvents,
            'totalSponsors' => $totalSponsors,
            'totalBudget' => round($totalBudget / 1000, 1),
            'filters' => compact('searchTerm', 'type', 'status', 'period'),
            'exportDate' => new \DateTime()
        ]);
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('AutiCare');
        $pdf->SetAuthor('AutiCare');
        $pdf->SetTitle('Liste des événements');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return new Response($pdf->Output('auticare_evenements.pdf', 'D'), 200, [
            'Content-Type' => 'application/pdf',
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
}