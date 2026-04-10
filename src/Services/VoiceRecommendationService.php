<?php

namespace App\Service;

use App\Repository\EventRepository;
use Psr\Log\LoggerInterface;

class VoiceRecommendationService
{
    private AssemblyAiService $assemblyAi;
    private GroqService $groq;
    private EventRepository $eventRepository;
    private LoggerInterface $logger;

    public function __construct(
        AssemblyAiService $assemblyAi,
        GroqService $groq,
        EventRepository $eventRepository,
        LoggerInterface $logger
    ) {
        $this->assemblyAi = $assemblyAi;
        $this->groq = $groq;
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;
    }

    /**
     * Analyse un fichier audio et retourne des recommandations d'événements
     */
   public function analyzeAndRecommend(string $audioFilePath, ?string $selectedEmotion = null): array
{
    try {
        $this->logger->info('=== Service analyzeAndRecommend START ===');
        
        // 1. Transcrire l'audio avec AssemblyAI
        $transcript = $this->assemblyAi->transcribe($audioFilePath);
        
        if (!$transcript || empty($transcript['text'])) {
            return [
                'success' => false,
                'error' => 'Impossible de transcrire l\'audio'
            ];
        }

        $userText = $transcript['text'];
        $this->logger->info('Texte transcrit: ' . $userText);
        
        // 2. Analyser le texte avec Groq pour détecter l'émotion
        try {
            $analysis = $this->groq->analyzeUserRequest($userText);
            $emotion = $analysis['emotion'] ?? ($selectedEmotion ?? 'neutre');
            $confidence = $analysis['confidence'] ?? 0.8;
        } catch (\Exception $e) {
            $this->logger->warning('Groq analyse error: ' . $e->getMessage());
            $emotion = $selectedEmotion ?? 'neutre';
            $confidence = 0.7;
        }
        
        $this->logger->info('Émotion détectée: ' . $emotion);
        
        // 3. Générer un conseil personnalisé (avec fallback)
        try {
            $advice = $this->groq->generateAdvice($userText, $emotion);
            if (empty($advice)) {
                $advice = $this->getFallbackAdvice($emotion);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Groq advice error: ' . $e->getMessage());
            $advice = $this->getFallbackAdvice($emotion);
        }
        
        // 4. Chercher des événements correspondants
        $allEvents = $this->eventRepository->findAll();
        $recommendedEvents = $this->findMatchingEventsWithScores($emotion, $userText, $allEvents);
        
        return [
            'success' => true,
            'user_text' => $userText,
            'emotion' => $emotion,
            'confidence' => $confidence,
            'advice' => $advice,
            'recommended_events' => $recommendedEvents
        ];
        
    } catch (\Exception $e) {
        $this->logger->error('Erreur service: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

private function getFallbackAdvice(string $emotion): string
{
    $advices = [
        'joyeux' => 'Continue à rayonner ! Ta joie est contagieuse. 🌟 Profite de chaque instant et partage ce bonheur autour de toi. 💛',
        'triste' => 'C\'est okay d\'être triste. Prends le temps de respirer. Un câlin virtuel pour toi 🤗 Les choses vont s\'arranger.',
        'colere' => 'Ta colère est légitime. Essaie de compter jusqu\'à 10 ou de dessiner ce que tu ressens. 🎨 La colère passe, elle ne dure pas.',
        'peur' => 'La peur nous protège mais ne nous arrête pas. Respire profondément. Tu es plus fort(e) que tes peurs. 💪',
        'neutre' => 'C\'est une belle journée pour prendre soin de toi. 🌸 Écoute ce dont ton cœur a besoin.'
    ];
    
    return $advices[$emotion] ?? $advices['neutre'];
}

    /**
     * Trouve des événements avec scores (fallback si pas d'IA)
     */
    private function findMatchingEventsWithScores(string $emotion, string $userText, array $allEvents): array
    {
        if (empty($allEvents)) {
            return $this->getFallbackEvents();
        }
        
        $keywords = [];
        $baseScore = 70;
        
        switch ($emotion) {
            case 'joyeux':
                $keywords = ['fête', 'concert', 'social', 'spectacle', 'jeu', 'anniversaire'];
                $baseScore = 85;
                break;
            case 'triste':
                $keywords = ['bien-être', 'relaxation', 'méditation', 'calme', 'soutien', 'yoga'];
                $baseScore = 90;
                break;
            case 'colere':
                $keywords = ['sport', 'activité', 'défoulement', 'musique', 'art', 'boxe'];
                $baseScore = 88;
                break;
            case 'peur':
                $keywords = ['sensoriel', 'calme', 'détente', 'méditation', 'nature', 'respirer'];
                $baseScore = 92;
                break;
            default:
                $keywords = ['découverte', 'atelier', 'rencontre', 'culturel'];
                $baseScore = 75;
        }
        
        $scoredEvents = [];
        foreach ($allEvents as $event) {
            $title = strtolower($this->getEventTitle($event));
            $desc = strtolower($this->getEventDescription($event));
            $type = strtolower($this->getEventType($event));
            
            $score = $baseScore;
            $raison = "Recommandé pour ton état d'esprit";
            
            foreach ($keywords as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    $score += 10;
                    $raison = "Parfaitement adapté à ton émotion";
                    break;
                }
                if (strpos($desc, $keyword) !== false) {
                    $score += 5;
                    $raison = "Très pertinent pour ce que tu ressens";
                    break;
                }
                if (strpos($type, $keyword) !== false) {
                    $score += 8;
                    $raison = "Exactement ce qu'il te faut";
                    break;
                }
            }
            
            $score = min(100, $score);
            
            $scoredEvents[] = [
                'id' => $this->getEventId($event),
                'title' => $this->getEventTitle($event),
                'type' => $this->getEventType($event),
                'date' => $this->formatEventDate($event),
                'location' => $this->getEventLocation($event),
                'description' => $this->getEventDescription($event),
                'emoji' => $this->getEmojiForEvent($event),
                'participants' => $this->getEventParticipants($event),
                'score' => $score,
                'raison' => $raison
            ];
        }
        
        // Trier par score décroissant
        usort($scoredEvents, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return array_slice($scoredEvents, 0, 3);
    }

    private function getFallbackEvents(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Atelier Détente',
                'type' => 'Bien-être',
                'date' => 'À venir',
                'location' => 'En ligne',
                'description' => 'Un moment pour se relaxer',
                'emoji' => '🧘',
                'participants' => 0,
                'score' => 95,
                'raison' => 'Parfait pour te détendre'
            ],
            [
                'id' => 2,
                'title' => 'Méditation Guidée',
                'type' => 'Relaxation',
                'date' => 'À venir',
                'location' => 'En ligne',
                'description' => 'Retrouve ton calme intérieur',
                'emoji' => '🕊️',
                'participants' => 0,
                'score' => 88,
                'raison' => 'Idéal pour apaiser ton esprit'
            ],
            [
                'id' => 3,
                'title' => 'Groupe de Partage',
                'type' => 'Social',
                'date' => 'À venir',
                'location' => 'En ligne',
                'description' => 'Partage tes émotions en toute sécurité',
                'emoji' => '💬',
                'participants' => 0,
                'score' => 82,
                'raison' => 'Exprime ce que tu ressens'
            ]
        ];
    }

    // ========== HELPERS POUR L'ENTITÉ EVENT ==========
    
    private function getEventId($event): ?int
    {
        return method_exists($event, 'getIdEvent') ? $event->getIdEvent() : 
               (method_exists($event, 'getId') ? $event->getId() : null);
    }

    private function getEventTitle($event): string
    {
        return method_exists($event, 'getTitre') ? ($event->getTitre() ?? '') :
               (method_exists($event, 'getTitle') ? ($event->getTitle() ?? '') : '');
    }

    private function getEventType($event): string
    {
        return method_exists($event, 'getTypeEvent') ? ($event->getTypeEvent() ?? '') :
               (method_exists($event, 'getType') ? ($event->getType() ?? '') : '');
    }

    private function getEventDescription($event): string
    {
        return method_exists($event, 'getDescription') ? ($event->getDescription() ?? '') : '';
    }

    private function getEventLocation($event): string
    {
        return method_exists($event, 'getLieu') ? ($event->getLieu() ?? '') :
               (method_exists($event, 'getLocation') ? ($event->getLocation() ?? '') : 'Lieu non spécifié');
    }

    private function getEventParticipants($event): int
    {
        return method_exists($event, 'getMaxParticipant') ? ($event->getMaxParticipant() ?? 0) : 0;
    }

    private function formatEventDate($event): string
    {
        $date = method_exists($event, 'getDateDebut') ? $event->getDateDebut() :
                (method_exists($event, 'getDate') ? $event->getDate() : null);
        
        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }
        return 'Date à venir';
    }

    private function getEmojiForEvent($event): string
    {
        $type = strtolower($this->getEventType($event));
        if (strpos($type, 'social') !== false) return '🎉';
        if (strpos($type, 'support') !== false) return '💙';
        if (strpos($type, 'sport') !== false) return '⚽';
        if (strpos($type, 'sensoriel') !== false) return '🧘';
        if (strpos($type, 'workshop') !== false) return '🎨';
        return '📅';
    }
}