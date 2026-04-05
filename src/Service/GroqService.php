<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GroqService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $groqApiKey,
        string $groqApiUrl,
        string $groqModel
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $groqApiKey;
        $this->apiUrl = rtrim($groqApiUrl, '/');
        $this->model = $groqModel;
    }

    /**
     * Appel à l'API Groq (comme appelGemini en Java)
     */
    private function callGroq(string $prompt): string
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 500
                ]
            ]);
            
            $data = $response->toArray();
            
            if (isset($data['error'])) {
                throw new \Exception('Groq API error: ' . json_encode($data['error']));
            }
            
            return $data['choices'][0]['message']['content'] ?? '';
            
        } catch (\Exception $e) {
            $this->logger->error('Groq call error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Nettoie la réponse JSON (comme en Java)
     */
    private function nettoyerReponseJSON(string $texte): string
    {
        // Enlever tout ce qui est avant le premier {
        $debut = strpos($texte, '{');
        if ($debut !== false) {
            $texte = substr($texte, $debut);
        }
        
        // Enlever tout ce qui est après le dernier }
        $fin = strrpos($texte, '}');
        if ($fin !== false) {
            $texte = substr($texte, 0, $fin + 1);
        }
        
        return $texte;
    }

    /**
     * Nettoie la réponse texte (comme en Java)
     */
    private function nettoyerReponse(string $texte): string
    {
        // Supprimer les phrases d'introduction
        $texte = preg_replace('/^voici\s+une\s+description\s*:?\s*/i', '', $texte);
        $texte = preg_replace('/^d\'accord\s*,?\s*voici\s*:?\s*/i', '', $texte);
        $texte = preg_replace('/^je\s+vous\s+propose\s*:?\s*/i', '', $texte);
        $texte = preg_replace('/^bien sûr\s*,?\s*voici\s*:?\s*/i', '', $texte);
        
        // Supprimer les symboles markdown
        $texte = preg_replace('/\*\*/', '', $texte);
        $texte = preg_replace('/\*/', '', $texte);
        $texte = preg_replace('/_/', '', $texte);
        $texte = preg_replace('/#/', '', $texte);
        
        return trim($texte);
    }

    /**
     * Analyse l'émotion (comme analyserTexteAvecFallback en Java)
     */
    public function analyzeUserRequest(string $text): array
    {
        try {
            $prompt = sprintf(
                "Analyse le texte suivant et détermine l'émotion dominante parmi: happy, sad, angry, fearful, surprised, disgusted, neutral.\n" .
                "Texte: \"%s\"\n" .
                "Retourne UNIQUEMENT le nom de l'émotion en anglais, rien d'autre.",
                $text
            );
            
            $emotion = $this->callGroq($prompt);
            $emotion = strtolower(trim($emotion));
            
            // Mapping anglais -> français
            $emotionMap = [
                'happy' => 'joyeux',
                'sad' => 'triste',
                'angry' => 'colere',
                'fearful' => 'peur',
                'surprised' => 'surpris',
                'disgusted' => 'degoûte',
                'neutral' => 'neutre'
            ];
            
            $emotionFr = $emotionMap[$emotion] ?? 'neutre';
            $keywords = $this->extractKeywords($text);
            
            $this->logger->info('Émotion détectée: ' . $emotionFr);
            
            return [
                'emotion' => $emotionFr,
                'keywords' => $keywords,
                'emotion_raw' => $emotion
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Analyse échouée, fallback mots-clés: ' . $e->getMessage());
            return [
                'emotion' => $this->detectEmotionFromText($text),
                'keywords' => $this->extractKeywords($text)
            ];
        }
    }

    /**
     * Génère un conseil (comme genererConseil en Java)
     */
   /**
 * Génère un conseil (comme genererConseil en Java)
 * Retourne une string (pas un array)
 */
public function generateAdvice(string $userText, string $emotion): string
{
    try {
        $prompt = sprintf(
            "Génère un conseil bienveillant et réconfortant pour une personne qui se sent %s.\n" .
            "La personne a dit: \"%s\".\n" .
            "Le conseil doit être court (2-3 phrases), positif, adapté aux enfants autistes.\n" .
            "Utilise un ton doux et encourageant.\n" .
            "Retourne UNIQUEMENT le conseil, sans introduction ni guillemets.",
            $emotion, $userText
        );
        
        $adviceText = $this->callGroq($prompt);
        $adviceText = $this->nettoyerReponse($adviceText);
        
        if (empty($adviceText)) {
            return $this->getFallbackAdvice($emotion);
        }
        
        return $adviceText;
        
    } catch (\Exception $e) {
        $this->logger->error('Génération conseil échouée: ' . $e->getMessage());
        return $this->getFallbackAdvice($emotion);
    }
}

/**
 * Conseils de secours (fallback)
 */
private function getFallbackAdvice(string $emotion): string
{
    $advices = [
        'joyeux' => 'Continue à rayonner ! Ta joie est contagieuse. 🌟 Profite de chaque instant. 💛',
        'triste' => 'C\'est okay d\'être triste. Prends le temps de respirer. Un câlin virtuel pour toi 🤗',
        'colere' => 'Ta colère est légitime. Compte jusqu\'à 10 ou dessine ce que tu ressens. 🎨',
        'peur' => 'La peur nous protège. Respire profondément. Tu es plus fort(e) que tes peurs. 💪',
        'neutre' => 'C\'est une belle journée pour prendre soin de toi. 🌸 Écoute ton cœur.'
    ];
    
    return $advices[$emotion] ?? $advices['neutre'];
}

    /**
     * Génère une explication de l'émotion (comme genererExplicationEmotion en Java)
     */
    public function generateEmotionExplanation(string $emotion): string
    {
        try {
            $prompt = sprintf(
                "Explique simplement l'émotion '%s' pour un enfant autiste.\n" .
                "Utilise un langage simple et des exemples concrets.\n" .
                "Décris comment on reconnaît cette émotion.\n" .
                "3-4 phrases maximum.\n" .
                "Retourne UNIQUEMENT l'explication, sans introduction.",
                $emotion
            );
            
            return $this->nettoyerReponse($this->callGroq($prompt));
            
        } catch (\Exception $e) {
            return "L'émotion $emotion est normale. On la ressent tous parfois.";
        }
    }

    /**
     * Recommande des événements basés sur l'émotion (comme recommanderEvenements en Java)
     */
    public function recommendEventsByEmotion(string $emotion, string $userText, array $events): array
    {
        try {
            // Construire la liste des événements
            $eventsList = [];
            foreach ($events as $i => $event) {
                $eventsList[] = [
                    'index' => $i,
                    'titre' => $this->getEventTitle($event),
                    'type' => $this->getEventType($event),
                    'description' => $this->getEventDescription($event)
                ];
            }
            
            $prompt = sprintf(
                "L'utilisateur se sent %s. Il a dit: \"%s\".\n\n" .
                "Voici la liste des événements disponibles:\n%s\n\n" .
                "IMPORTANT: Analyse le TITRE et la DESCRIPTION de chaque événement pour déterminer " .
                "lesquels sont les plus adaptés à quelqu'un qui se sent %s.\n\n" .
                "Retourne UNIQUEMENT un objet JSON valide avec cette structure EXACTE:\n" .
                "{\n" .
                "  \"recommendations\": [\n" .
                "    {\n" .
                "      \"index\": 0,\n" .
                "      \"score\": 95,\n" .
                "      \"raison\": \"Parfait pour exprimer ta joie\"\n" .
                "    },\n" .
                "    {\n" .
                "      \"index\": 2,\n" .
                "      \"score\": 87,\n" .
                "      \"raison\": \"Profite de ton énergie\"\n" .
                "    }\n" .
                "  ]\n" .
                "}\n\n" .
                "Retourne les 3 meilleurs événements, triés par score décroissant.",
                $emotion, $userText, json_encode($eventsList, JSON_PRETTY_PRINT), $emotion
            );
            
            $result = $this->callGroq($prompt);
            $result = $this->nettoyerReponseJSON($result);
            
            return $this->parseRecommendations($result, $events);
            
        } catch (\Exception $e) {
            $this->logger->error('Recommandation échouée: ' . $e->getMessage());
            return array_slice($events, 0, 3);
        }
    }

    /**
     * Parse les recommandations JSON (comme parserRecommandations en Java)
     */
    private function parseRecommendations(string $jsonResponse, array $allEvents): array
    {
        $recommendedEvents = [];
        
        try {
            $data = json_decode($jsonResponse, true);
            
            if (!isset($data['recommendations'])) {
                return array_slice($allEvents, 0, 3);
            }
            
            $recommendations = $data['recommendations'];
            
            // Trier par score décroissant
            usort($recommendations, function($a, $b) {
                return $b['score'] - $a['score'];
            });
            
            foreach ($recommendations as $rec) {
                $index = $rec['index'];
                if (isset($allEvents[$index])) {
                    $recommendedEvents[] = $allEvents[$index];
                }
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Parse recommandations error: ' . $e->getMessage());
            return array_slice($allEvents, 0, 3);
        }
        
        return $recommendedEvents;
    }

    /**
     * Méthodes helper pour les événements (à adapter selon votre entité)
     */
    private function getEventTitle($event): string
    {
        if (method_exists($event, 'getTitle')) {
            return $event->getTitle() ?? '';
        }
        if (method_exists($event, 'getTitre')) {
            return $event->getTitre() ?? '';
        }
        return '';
    }

    private function getEventType($event): string
    {
        if (method_exists($event, 'getType')) {
            return $event->getType() ?? '';
        }
        if (method_exists($event, 'getTypeEvent')) {
            return $event->getTypeEvent() ?? '';
        }
        return '';
    }

    private function getEventDescription($event): string
    {
        if (method_exists($event, 'getDescription')) {
            return $event->getDescription() ?? '';
        }
        return '';
    }

    private function getAdviceTitle(string $emotion): string
    {
        $titles = [
            'joyeux' => '🎉 Profite de ta joie !',
            'triste' => '💪 Un câlin virtuel pour toi',
            'colere' => '😌 Respire un grand coup',
            'peur' => '🛡️ Tu es en sécurité',
            'surpris' => '✨ Quelle belle surprise !',
            'degoûte' => '🍃 Passons à autre chose',
            'neutre' => '💫 Un petit rayon de soleil'
        ];
        return $titles[$emotion] ?? '✨ Conseil personnalisé';
    }

    private function detectEmotionFromText(string $text): string
    {
        $text = strtolower($text);
        
        if (strpos($text, 'perdu') !== false && strpos($text, 'gagné') !== false) {
            return 'triste';
        }
        
        $emotionMap = [
            'joyeux' => ['content', 'heureux', 'joie', 'bonheur', 'sourire', 'gagné', 'super', 'génial'],
            'triste' => ['perdu', 'triste', 'pleure', 'mal', 'déçu', 'chagrin', 'peine'],
            'colere' => ['colère', 'fâché', 'énervé', 'angry', 'mad'],
            'peur' => ['peur', 'anxieux', 'inquiet', 'fear', 'scared'],
            'surpris' => ['surprise', 'étonné', 'wow'],
            'degoûte' => ['dégout', 'beurk']
        ];
        
        foreach ($emotionMap as $emotion => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $emotion;
                }
            }
        }
        
        return 'neutre';
    }

    private function extractKeywords(string $text): array
    {
        $stopWords = ['je', 'tu', 'il', 'elle', 'nous', 'vous', 'ils', 'elles', 'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'et', 'ou', 'mais', 'donc', 'car', 'pour', 'par', 'avec', 'sans', 'chez', 'dans', 'sur', 'sous', 'entre', 'vers', 'à', 'au', 'aux'];
        
        $words = explode(' ', strtolower($text));
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        return array_values(array_unique($keywords));
    }
    /**
 * Appel Groq pour les recommandations (utilisé par le contrôleur)
 * Retourne la réponse brute de l'API
 */
public function callGroqForPrompt(string $prompt): string
{
    return $this->callGroq($prompt);
}
}