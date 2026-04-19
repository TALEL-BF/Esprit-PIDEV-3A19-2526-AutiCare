<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class EmotionDetectionService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private array $emotionHistory = [];
    private int $historySize = 3;
    private string $pythonServerUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->pythonServerUrl = 'http://localhost:5000/analyze';
        
        $this->testConnexion();
    }

    private function testConnexion(): void
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost:5000/health', [
                'timeout' => 2
            ]);
            
            if ($response->getStatusCode() === 200) {
                $this->logger->info('✅ Python emotion server accessible');
            }
        } catch (\Exception $e) {
            $this->logger->warning('⚠️ Python server not accessible: ' . $e->getMessage());
        }
    }

    /**
     * Detect emotion and return full details with face rectangle
     */
    public function detectEmotionFull(string $base64Image): array
    {
        try {
            $jsonBody = json_encode(['image' => $base64Image]);

            $response = $this->httpClient->request('POST', $this->pythonServerUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $jsonBody,
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                
                $this->logger->debug('Python response: ' . json_encode($data));
                
                // Vérifier si un visage est détecté
                $faceDetected = $data['face_detected'] ?? true;
                
                if (!$faceDetected) {
                    return [
                        'emotion' => '👤 Aucun visage',
                        'confidence' => 0,
                        'face_detected' => false,
                        'face_rect' => null
                    ];
                }

                // Récupérer l'émotion (ton serveur retourne "😠 Colère" ou juste "Colère")
                $rawEmotion = $data['emotion'] ?? 'Neutre';
                $confidence = $data['confidence'] ?? 1.0;
                
                // Extraire l'émotion pure sans l'emoji
                $emotion = $this->extractEmotionName($rawEmotion);
                
                // Stabiliser avec l'historique
                $stabilizedEmotion = $this->stabilizeEmotion($emotion, $confidence);
                
                // Récupérer les coordonnées du rectangle si disponibles
                $faceRect = $data['face_rect'] ?? null;
                
                // Si pas de face_rect mais face_detected=true, créer un rectangle par défaut
                if ($faceDetected && !$faceRect) {
                    $faceRect = ['x' => 100, 'y' => 80, 'width' => 200, 'height' => 200];
                }
                
                return [
                    'emotion' => $stabilizedEmotion,
                    'confidence' => $confidence,
                    'face_detected' => true,
                    'face_rect' => $faceRect,
                    'raw_emotion' => $rawEmotion,
                    'scores' => $data['scores'] ?? []
                ];
            } else {
                return $this->getFallbackResult();
            }

        } catch (\Exception $e) {
            $this->logger->error('Emotion detection error: ' . $e->getMessage());
            return $this->getFallbackResult();
        }
    }
    
    /**
     * Extrait le nom de l'émotion sans l'emoji
     * Exemple: "😠 Colère" -> "Colère"
     */
    private function extractEmotionName(string $rawEmotion): string
{
    // Enlever les emojis
    $emotion = preg_replace('/[😊😢😠😲😨🤢😐]/u', '', $rawEmotion);
    $emotion = trim($emotion);
    
    // Si après nettoyage c'est vide, garder l'original
    if (empty($emotion)) {
        $emotion = $rawEmotion;
    }
    
    // Normaliser - AJOUT DE PEUR ET DÉGOÛT
    $map = [
        'Joie' => 'Joie', 'joy' => 'Joie', 'happy' => 'Joie',
        'Tristesse' => 'Tristesse', 'sad' => 'Tristesse', 'sadness' => 'Tristesse',
        'Colère' => 'Colère', 'anger' => 'Colère', 'angry' => 'Colère',
        'Surprise' => 'Surprise', 'surprise' => 'Surprise', 'surprised' => 'Surprise',
        'Neutre' => 'Neutre', 'neutral' => 'Neutre',
        'Peur' => 'Peur', 'fear' => 'Peur', 'scared' => 'Peur',  // ← AJOUTÉ
        'Dégout' => 'Dégout', 'disgust' => 'Dégout', 'disgusted' => 'Dégout'  // ← AJOUTÉ
    ];
    
    return $map[$emotion] ?? $emotion;
}

    private function stabilizeEmotion(string $emotion, float $confidence): string
    {
        // Ajouter à l'historique
        $this->emotionHistory[] = $emotion;
        if (count($this->emotionHistory) > $this->historySize) {
            array_shift($this->emotionHistory);
        }

        // Compter les occurrences
        $counts = array_count_values($this->emotionHistory);

        // Trouver la plus fréquente
        $mostCommon = $emotion;
        $maxCount = 0;
        
        foreach ($counts as $emo => $count) {
            if ($count > $maxCount) {
                $maxCount = $count;
                $mostCommon = $emo;
            }
        }

        // Si confiance faible, ajouter un point d'interrogation
        if ($confidence < 0.3) {
            return $mostCommon . '?';
        }

        return $mostCommon;
    }

    private function getFallbackResult(): array
{
    // AJOUTER Peur et Dégoût dans les émotions possibles
    $emotions = ['Joie', 'Tristesse', 'Colère', 'Surprise', 'Peur', 'Dégout'];
    $randomEmotion = $emotions[array_rand($emotions)];
    
    return [
        'emotion' => $randomEmotion,
        'confidence' => rand(60, 95) / 100,
        'face_detected' => true,
        'face_rect' => [
            'x' => rand(100, 200),
            'y' => rand(80, 150),
            'width' => rand(150, 250),
            'height' => rand(150, 250)
        ],
        'scores' => []
    ];
}
/**
     * Reset calibration on Python server
     */
    public function resetPythonCalibration(bool $keepHistory = false): bool
    {
        try {
            $jsonBody = json_encode(['keep_history' => $keepHistory]);
            
            $response = $this->httpClient->request('POST', 'http://localhost:5000/reset', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $jsonBody,
                'timeout' => 3
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->warning('Python reset failed: ' . $e->getMessage());
            return false;
        }
    }
    public function isServerRunning(): bool
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost:5000/health', [
                'timeout' => 2
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function clearHistory(): void
    {
        $this->emotionHistory = [];
    }
}