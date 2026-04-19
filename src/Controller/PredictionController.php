<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\EventAIPredictor;
use App\Service\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PredictionController extends AbstractController
{
    #[Route('/admin/predictions', name: 'admin_predictions')]
    public function index(EventRepository $eventRepository, EventAIPredictor $predictor): Response
    {
        $events = $eventRepository->findAll();
        
        if (empty($events)) {
            return $this->render('admin/pages/event_predictions.html.twig', [
                'predictions' => [],
                'averageScore' => 0,
                'totalEvents' => 0,
                'bestScore' => 0,
                'bestEventName' => '',
                'goodEventsCount' => 0,
                'lowScoreCount' => 0,
                'goodEventsPercent' => 0,
                'modelTrained' => false,
                'statistics' => []
            ]);
        }
        
        // Load existing model or train new one
        $modelLoaded = $predictor->loadModel();
        if (!$modelLoaded) {
            $predictor->train($events);
        }
        
        $predictions = [];
        $totalScore = 0;
        $bestScore = 0;
        $bestEventName = '';
        $goodEventsCount = 0;
        $lowScoreCount = 0;
        $statistics = [
            'by_type' => [],
            'by_day' => [],
            'by_capacity' => []
        ];
        
        foreach ($events as $event) {
            $score = $predictor->predictAutismScore($event);
            $level = $this->getScoreLevel($score);
            
            // Update best score
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEventName = $event->getTitre();
            }
            
            // Update counts
            if ($score >= 70) {
                $goodEventsCount++;
            } elseif ($score < 40) {
                $lowScoreCount++;
            }
            
            // Statistics by type
            $type = $event->getTypeEvent() ?? 'Autre';
            if (!isset($statistics['by_type'][$type])) {
                $statistics['by_type'][$type] = ['count' => 0, 'total' => 0];
            }
            $statistics['by_type'][$type]['count']++;
            $statistics['by_type'][$type]['total'] += $score;
            
            // Statistics by day
            if ($event->getDateDebut()) {
                $day = $event->getDateDebut()->format('l');
                if (!isset($statistics['by_day'][$day])) {
                    $statistics['by_day'][$day] = ['count' => 0, 'total' => 0];
                }
                $statistics['by_day'][$day]['count']++;
                $statistics['by_day'][$day]['total'] += $score;
            }
            
            // Statistics by capacity
            $capacity = $event->getMaxParticipant();
            if ($capacity <= 15) {
                $group = 'Très petit (≤15)';
            } elseif ($capacity <= 30) {
                $group = 'Petit (16-30)';
            } elseif ($capacity <= 50) {
                $group = 'Moyen (31-50)';
            } elseif ($capacity <= 100) {
                $group = 'Grand (51-100)';
            } else {
                $group = 'Très grand (>100)';
            }
            
            if (!isset($statistics['by_capacity'][$group])) {
                $statistics['by_capacity'][$group] = ['count' => 0, 'total' => 0];
            }
            $statistics['by_capacity'][$group]['count']++;
            $statistics['by_capacity'][$group]['total'] += $score;
            
            // 🔥 Structure compatible avec le template
            $predictions[] = [
                'event' => $event,  // ← Garde l'objet event complet
                'id' => $event->getIdEvent(),
                'title' => $event->getTitre(),
                'type' => $event->getTypeEvent(),
                'date' => $event->getDateDebut() ? $event->getDateDebut()->format('d/m/Y') : 'N/A',
                'capacity' => $event->getMaxParticipant(),
                'score' => round($score, 1),
                'level' => $level,
                'level_class' => $this->getScoreClass($score),
                'level_icon' => $this->getScoreIcon($score),
                'description' => substr($event->getDescription() ?? '', 0, 100) . '...'
            ];
            $totalScore += $score;
        }
        
        // Calculate average by type
        foreach ($statistics['by_type'] as $type => $data) {
            $statistics['by_type'][$type]['average'] = round($data['total'] / $data['count'], 1);
        }
        
        foreach ($statistics['by_day'] as $day => $data) {
            $statistics['by_day'][$day]['average'] = round($data['total'] / $data['count'], 1);
        }
        
        foreach ($statistics['by_capacity'] as $group => $data) {
            $statistics['by_capacity'][$group]['average'] = round($data['total'] / $data['count'], 1);
        }
        
        // Sort predictions by score
        usort($predictions, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        $totalEvents = count($events);
        $goodEventsPercent = $totalEvents > 0 ? round(($goodEventsCount / $totalEvents) * 100, 1) : 0;
        
        return $this->render('admin/pages/event_predictions.html.twig', [
            'predictions' => $predictions,
            'averageScore' => $totalEvents > 0 ? round($totalScore / $totalEvents, 1) : 0,
            'totalEvents' => $totalEvents,
            'bestScore' => round($bestScore, 1),
            'bestEventName' => $bestEventName,
            'goodEventsCount' => $goodEventsCount,
            'lowScoreCount' => $lowScoreCount,
            'goodEventsPercent' => $goodEventsPercent,
            'modelTrained' => $predictor->isTrained(),
            'statistics' => $statistics
        ]);
    }
    
    #[Route('/admin/predictions/train', name: 'admin_predictions_train', methods: ['POST'])]
    public function trainModel(EventRepository $eventRepository, EventAIPredictor $predictor): JsonResponse
    {
        $events = $eventRepository->findAll();
        
        if (empty($events)) {
            return $this->json([
                'success' => false, 
                'message' => 'Aucun événement disponible pour l\'entraînement'
            ], 400);
        }
        
        try {
            $startTime = microtime(true);
            $predictor->train($events);
            $trainingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return $this->json([
                'success' => true,
                'message' => 'Modèle entraîné avec succès sur ' . count($events) . ' événements',
                'events_count' => count($events),
                'training_time_ms' => $trainingTime
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Erreur lors de l\'entraînement: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/admin/predictions/ai-suggestions', name: 'admin_predictions_ai_suggestions', methods: ['POST'])]
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
    
    #[Route('/admin/predictions/edit/{id}', name: 'admin_predictions_edit', methods: ['GET'])]
    public function editWithSuggestion(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }
        
        $suggestField = $request->query->get('field');
        $suggestValue = $request->query->get('value');
        $suggestPoints = $request->query->get('points');
        
        if ($suggestField && $suggestValue) {
            $request->getSession()->set('ai_suggestion', [
                'field' => $suggestField,
                'value' => $suggestValue,
                'points' => $suggestPoints,
                'eventId' => $id
            ]);
        }
        
        return $this->redirectToRoute('admin_evenements', ['edit' => $id]);
    }
    
    #[Route('/admin/predictions/get-suggestion', name: 'admin_predictions_get_suggestion', methods: ['GET'])]
    public function getSuggestion(Request $request): JsonResponse
    {
        $suggestion = $request->getSession()->get('ai_suggestion');
        $request->getSession()->remove('ai_suggestion');
        
        return $this->json([
            'success' => true,
            'suggestion' => $suggestion
        ]);
    }
    
    #[Route('/admin/predictions/refresh', name: 'admin_predictions_refresh', methods: ['POST'])]
    public function refreshPredictions(EventRepository $eventRepository, EventAIPredictor $predictor): JsonResponse
    {
        $events = $eventRepository->findAll();
        $predictions = [];
        
        foreach ($events as $event) {
            $score = $predictor->predictAutismScore($event);
            $predictions[] = [
                'id' => $event->getIdEvent(),
                'title' => $event->getTitre(),
                'score' => round($score, 1),
                'level' => $this->getScoreLevel($score),
                'level_class' => $this->getScoreClass($score)
            ];
        }
        
        usort($predictions, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $this->json([
            'success' => true,
            'predictions' => $predictions
        ]);
    }
    
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