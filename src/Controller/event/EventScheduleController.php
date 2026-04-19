<?php

namespace App\Controller\event;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Services\AiPlanningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/event-schedule')]
class EventScheduleController extends AbstractController
{
    #[Route('/{id}', name: 'admin_event_schedule_index', methods: ['GET'])]
    public function index(int $id, EventRepository $eventRepository): Response
    {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }
        
        return $this->render('admin/pages/event/event_planning.html.twig', [
            'event' => $event
        ]);
    }
    
    #[Route('/get/{id}', name: 'admin_event_schedule_get', methods: ['GET'])]
    public function getSchedule(int $id, EventRepository $eventRepository, AiPlanningService $aiService): JsonResponse
    {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'error' => 'Événement non trouvé'], 404);
        }
        
        $planning = $aiService->getPlanning($event);
        
        return $this->json([
            'success' => true,
            'planning' => $planning ?? ['creneaux' => []]
        ]);
    }
    
    #[Route('/generate/{id}', name: 'admin_event_schedule_generate', methods: ['POST'])]
public function generateSchedule(int $id, EventRepository $eventRepository, AiPlanningService $aiService, EntityManagerInterface $em): JsonResponse
{
    try {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'error' => 'Événement non trouvé'], 404);
        }
        
        $planning = $aiService->generatePlanning($event, $em);
        
        return $this->json([
            'success' => true,
            'planning' => $planning
        ]);
        
    } catch (\Exception $e) {
        // Retourne l'erreur complète
        return $this->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
}
    
    #[Route('/save/{id}', name: 'admin_event_schedule_save', methods: ['POST'])]
    public function saveSchedule(int $id, Request $request, EventRepository $eventRepository, AiPlanningService $aiService, EntityManagerInterface $em): JsonResponse
    {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'error' => 'Événement non trouvé'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['planning'])) {
            return $this->json(['success' => false, 'error' => 'Données invalides'], 400);
        }
        
        $aiService->savePlanning($event, $data['planning'], $em);
        
        return $this->json(['success' => true]);
    }
    
    #[Route('/update-hours/{id}', name: 'admin_event_schedule_update_hours', methods: ['POST'])]
    public function updateEventHours(int $id, Request $request, EventRepository $eventRepository, EntityManagerInterface $em): JsonResponse
    {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            return $this->json(['success' => false, 'error' => 'Event not found'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['heure_debut'])) {
            $heureDebut = \DateTime::createFromFormat('H:i', $data['heure_debut']);
            if ($heureDebut) {
                $event->setHeureDebut($heureDebut);
            }
        }
        
        if (isset($data['heure_fin'])) {
            $heureFin = \DateTime::createFromFormat('H:i', $data['heure_fin']);
            if ($heureFin) {
                $event->setHeureFin($heureFin);
            }
        }
        
        $em->flush();
        
        return $this->json(['success' => true]);
    }
}