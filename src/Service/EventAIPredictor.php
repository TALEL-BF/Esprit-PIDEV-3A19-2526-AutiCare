<?php

namespace App\Service;

use App\Entity\Event;
use Psr\Log\LoggerInterface;
use DateTime;
use Calendar;

class EventAIPredictor
{
    private LoggerInterface $logger;
    private bool $isTrained = false;
    private array $trainingData = [];
    
    // Weights for different factors
    private array $weights = [
        'positive_keyword' => 8,
        'negative_keyword' => 15,
        'weekday_bonus' => 15,
        'weekend_penalty' => 20,
        'small_group_bonus' => 25,
        'large_group_penalty' => 25,
        'short_duration_bonus' => 20,
        'long_duration_penalty' => 20,
        'calm_type_bonus' => 20,
        'noisy_type_penalty' => 30,
    ];
    
    // Positive keywords (autism-friendly)
    private array $positiveKeywords = [
        'calme', 'silence', 'tranquille', 'zen', 'paisible',
        'accessible', 'adapté', 'structure', 'organisé',
        'petit groupe', 'intimiste', 'espace calme',
        'sans stress', 'bienveillant', 'sensoriel',
        'sans bruit', 'relaxation', 'méditation',
        'yoga', 'atelier calme', 'routine',
        'prévisible', 'sans surprise', 'sécurisé',
        'sensory-friendly', 'low-stimulation', 'chill', 'relaxed',
        'flexible seating', 'breaks offered', 'quiet corner',
        'visual schedule', 'clear agenda', 'predictable flow',
        'earplugs available', 'dim lights', 'no flashing',
        'individual attention', 'slow pace'
    ];
    
    // Negative keywords (not autism-friendly)
    private array $negativeKeywords = [
        'foule', 'bruyant', 'musique forte', 'lumières',
        'surprise', 'improvisation', 'grand public',
        'spectacle', 'feux', 'fête', 'party', 'festival',
        'concert', 'klaxon', 'alarme', 'chaotique',
        'imprévisible', 'changement', 'bousculade',
        'attente longue', 'cohue', 'loudspeaker', 'announcements',
        'applause', 'cheering', 'strong smells', 'perfume',
        'cologne', 'candles', 'unexpected changes', 'waiting line',
        'standing long', 'crowded', 'packed', 'shoulder to shoulder',
        'improvised', 'unstructured', 'chaotic energy'
    ];
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Train the model with past events
     * For now, we just store training data (simplified ML)
     */
    public function train(array $pastEvents): void
    {
        try {
            if (empty($pastEvents)) {
                $this->logger->warning('No past events for training');
                return;
            }
            
            $this->trainingData = [];
            
            foreach ($pastEvents as $event) {
                $score = $this->calculateAutismScore($event);
                $this->trainingData[] = [
                    'event' => $event,
                    'score' => $score,
                    'features' => $this->extractFeatures($event)
                ];
            }
            
            $this->isTrained = true;
            $this->logger->info('Model trained with ' . count($pastEvents) . ' events');
            
        } catch (\Exception $e) {
            $this->logger->error('Training error: ' . $e->getMessage());
        }
    }
    /**
 * Generate AI-powered suggestions using Groq
 */
public function generateSmartSuggestions(Event $event, GroqService $groqService): array
{
    $eventData = [
        'title' => $event->getTitre(),
        'type' => $event->getTypeEvent(),
        'description' => $event->getDescription(),
        'capacity' => $event->getMaxParticipant(),
        'currentScore' => $this->predictAutismScore($event)
    ];
    
    return $groqService->generateEventImprovementSuggestions($eventData);
}
    
    /**
     * Predict autism score for an event
     */
    public function predictAutismScore(Event $event): float
    {
        try {
            // Use the trained model if available
            if ($this->isTrained && !empty($this->trainingData)) {
                return $this->predictWithSimilarity($event);
            }
            
            // Fallback to keyword-based prediction
            return $this->predictFromKeywords($event);
            
        } catch (\Exception $e) {
            $this->logger->error('Prediction error: ' . $e->getMessage());
            return $this->predictFromKeywords($event);
        }
    }
    
    /**
     * Predict using similarity with trained events (simple ML)
     */
    private function predictWithSimilarity(Event $event): float
    {
        $currentFeatures = $this->extractFeatures($event);
        $totalScore = 0;
        $totalWeight = 0;
        
        foreach ($this->trainingData as $data) {
            $similarity = $this->calculateSimilarity($currentFeatures, $data['features']);
            $totalScore += $data['score'] * $similarity;
            $totalWeight += $similarity;
        }
        
        if ($totalWeight > 0) {
            return min(100, max(0, $totalScore / $totalWeight));
        }
        
        return $this->predictFromKeywords($event);
    }
    
    /**
     * Predict using only keywords (fallback)
     */
    public function predictFromKeywords(Event $event): float
    {
        $title = strtolower($event->getTitre() ?? '');
        $description = strtolower($event->getDescription() ?? '');
        $combined = $title . ' ' . $description;
        
        $score = 50.0; // Start neutral
        
        // Positive keywords
        $positiveCount = 0;
        foreach ($this->positiveKeywords as $keyword) {
            if (strpos($combined, $keyword) !== false) {
                $score += $this->weights['positive_keyword'];
                $positiveCount++;
            }
        }
        
        // Negative keywords
        $negativeCount = 0;
        foreach ($this->negativeKeywords as $keyword) {
            if (strpos($combined, $keyword) !== false) {
                $score -= $this->weights['negative_keyword'];
                $negativeCount++;
            }
        }
        
        // Day of week factor
        if ($event->getDateDebut()) {
            $dayOfWeek = (int)$event->getDateDebut()->format('N'); // 1=Monday, 7=Sunday
            
            if ($dayOfWeek <= 5) { // Weekday
                $score += $this->weights['weekday_bonus'];
            } else { // Weekend
                $score -= $this->weights['weekend_penalty'];
            }
        }
        
        // Participant count factor
        $maxParticipants = $event->getMaxParticipant() ?? 0;
        if ($maxParticipants > 0) {
            if ($maxParticipants <= 15) {
                $score += $this->weights['small_group_bonus'];
            } elseif ($maxParticipants <= 30) {
                $score += 15;
            } elseif ($maxParticipants <= 50) {
                $score += 5;
            } elseif ($maxParticipants <= 100) {
                $score -= 10;
            } else {
                $score -= $this->weights['large_group_penalty'];
            }
        }
        
        // Duration factor
        if ($event->getDateDebut() && $event->getDateFin()) {
            $diff = $event->getDateFin()->getTimestamp() - $event->getDateDebut()->getTimestamp();
            $hours = $diff / 3600;
            
            if ($hours <= 1) {
                $score += $this->weights['short_duration_bonus'];
            } elseif ($hours <= 2) {
                $score += 15;
            } elseif ($hours <= 3) {
                $score += 5;
            } elseif ($hours <= 4) {
                $score -= 5;
            } else {
                $score -= $this->weights['long_duration_penalty'];
            }
        }
        
        // Event type factor
        $eventType = strtolower($event->getTypeEvent() ?? '');
        $calmTypes = ['atelier', 'formation', 'yoga', 'méditation', 'thérapie', 'relaxation', 'lecture'];
        $noisyTypes = ['festival', 'concert', 'fête', 'party', 'soirée', 'gala', 'spectacle'];
        
        foreach ($calmTypes as $type) {
            if (strpos($eventType, $type) !== false) {
                $score += $this->weights['calm_type_bonus'];
                break;
            }
        }
        
        foreach ($noisyTypes as $type) {
            if (strpos($eventType, $type) !== false) {
                $score -= $this->weights['noisy_type_penalty'];
                break;
            }
        }
        
        // Bonus for many positives, no negatives
        if ($positiveCount >= 3 && $negativeCount === 0) {
            $score += 15;
        }
        
        // Penalty for many negatives
        if ($negativeCount >= 3) {
            $score -= 20;
        }
        
        return min(100, max(0, $score));
    }
    
    /**
     * Extract numerical features from event
     */
    private function extractFeatures(Event $event): array
    {
        return [
            'title_length' => strlen($event->getTitre() ?? ''),
            'desc_length' => strlen($event->getDescription() ?? ''),
            'day_of_week' => $event->getDateDebut() ? (int)$event->getDateDebut()->format('N') : 3,
            'month' => $event->getDateDebut() ? (int)$event->getDateDebut()->format('n') : 6,
            'duration_days' => $this->calculateDurationDays($event),
            'max_participants' => $event->getMaxParticipant() ?? 50,
        ];
    }
    
    /**
     * Calculate autism score using rules (for training)
     */
    private function calculateAutismScore(Event $event): float
    {
        return $this->predictFromKeywords($event);
    }
    
    /**
     * Calculate similarity between two feature sets
     */
    private function calculateSimilarity(array $features1, array $features2): float
    {
        $similarity = 1.0;
        $differences = 0;
        
        // Compare each feature
        foreach ($features1 as $key => $value1) {
            $value2 = $features2[$key];
            
            if ($value2 != 0) {
                $ratio = min($value1, $value2) / max($value1, $value2);
                $similarity *= $ratio;
            }
        }
        
        return max(0, min(1, $similarity));
    }
    
    /**
     * Calculate duration in days
     */
    private function calculateDurationDays(Event $event): int
    {
        if ($event->getDateDebut() && $event->getDateFin()) {
            $diff = $event->getDateFin()->getTimestamp() - $event->getDateDebut()->getTimestamp();
            return (int)($diff / 86400);
        }
        return 1;
    }
    
    /**
     * Check if model is trained
     */
    public function isTrained(): bool
    {
        return $this->isTrained;
    }
}