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
                'lowScoreCount' => 0
            ]);
        }
        
        // Train the model
        $predictor->train($events);
        
        $predictions = [];
        $totalScore = 0;
        $bestScore = 0;
        $bestEventName = '';
        $goodEventsCount = 0;
        $lowScoreCount = 0;
        
        foreach ($events as $event) {
            $score = $predictor->predictAutismScore($event);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEventName = $event->getTitre();
            }
            
            if ($score >= 70) {
                $goodEventsCount++;
            } elseif ($score < 40) {
                $lowScoreCount++;
            }
            
            $predictions[] = [
                'event' => $event,
                'score' => round($score, 1),
                'level' => $this->getScoreLevel($score)
            ];
            $totalScore += $score;
        }
        
        // Sort by score
        usort($predictions, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $this->render('admin/pages/event_predictions.html.twig', [
            'predictions' => $predictions,
            'averageScore' => count($events) > 0 ? round($totalScore / count($events), 1) : 0,
            'totalEvents' => count($events),
            'bestScore' => round($bestScore, 1),
            'bestEventName' => $bestEventName,
            'goodEventsCount' => $goodEventsCount,
            'lowScoreCount' => $lowScoreCount
        ]);
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
    
    // Get suggestion from URL
    $suggestField = $request->query->get('field');
    $suggestValue = $request->query->get('value');
    $suggestPoints = $request->query->get('points');
    
    // Store in session for the form to use
    if ($suggestField && $suggestValue) {
        $request->getSession()->set('ai_suggestion', [
            'field' => $suggestField,
            'value' => $suggestValue,
            'points' => $suggestPoints,
            'eventId' => $id
        ]);
    }
    
    // Redirect to main events page with edit parameter
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
    
    private function getScoreLevel(float $score): string
    {
        if ($score >= 70) return 'Très adapté';
        if ($score >= 40) return 'Modérément adapté';
        return 'Peu adapté';
    }
}