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
/**
 * Generate a story for children based on event name
 * (Like your Java genererHistoire method)
 */
/**
 * Generate a longer story for children (4-5 phrases)
 * Inclut toutes les émotions : Joie, Tristesse, Surprise, Colère, Peur, Dégoût
 */
public function genererHistoire(string $nomEvent): string
{
    $prompt = "Crée une courte histoire pour enfants (4-5 phrases) basée sur cet événement : '" . $nomEvent . "'.\n" .
              "L'histoire doit parler d'un petit animal mignon (lion, ours, lapin, éléphant) qui vit cet événement.\n" .
              "Inclus des moments avec ces émotions au choix (au moins 4 différentes) :\n" .
              "- 😊 Joie\n" .
              "- 😢 Tristesse\n" .
              "- 😲 Surprise\n" .
              "- 😠 Colère\n" .
              "- 😨 Peur\n" .
              "- 🤢 Dégoût\n\n" .
              "RÈGLES :\n" .
              "- La PREMIÈRE phrase doit commencer par 😊 (Joie)\n" .
              "- Chaque phrase doit avoir une émoticône DIFFÉRENTE au début\n" .
              "- Interdit d'utiliser 😐 Neutre\n" .
              "- L'histoire doit avoir un arc narratif : début joyeux → problème → résolution\n\n" .
              "IMPORTANT : Mets l'émoticône au DÉBUT de chaque phrase qui correspond à l'émotion ressentie.\n" .
              "Chaque phrase doit être sur une nouvelle ligne.\n\n" .
              "Exemple de format :\n" .
              "😊 Le petit lion était content d'aller au zoo.\n" .
              "😲 Soudain, un perroquet coloré lui a parlé !\n" .
              "😨 Il a eu peur quand le perroquet a crié très fort.\n" .
              "😢 Il était triste car ses oreilles lui faisaient mal.\n" .
              "😊 À la fin, le perroquet s'est excusé et ils sont devenus amis.\n\n" .
              "Retourne UNIQUEMENT l'histoire, sans introduction ni explications.";
    
    return $this->callGroq($prompt);
}

/**
 * Generate a story with EXACTLY 3 phrases for the emotion game
 * (Like your Java but simplified for the game)
 */
/**
 * Generate a story with EXACTLY 3 phrases for the emotion game
 * Règles: 1ère phrase = Joie, 2ème et 3ème = n'importe quelle émotion (Peur/Dégoût inclus)
 */
public function genererHistoireCourte(string $nomEvent): string
{
    $prompt = "Crée une histoire pour enfants basée sur: '" . $nomEvent . "'.\n" .
              "L'histoire doit avoir EXACTEMENT 3 phrases.\n" .
              "RÈGLES OBLIGATOIRES :\n" .
              "- La PREMIÈRE phrase doit commencer par 😊 (Joie)\n" .
              "- Les émotions possibles : 😊 Joie, 😢 Tristesse, 😠 Colère, 😲 Surprise, 😨 Peur, 🤢 Dégoût\n" .
              "- Interdit d'utiliser 😐 Neutre\n" .
              "- La 2ème et 3ème phrase peuvent utiliser N'IMPORTE QUELLE émotion (même répéter la même)\n" .
              "- Pas besoin d'utiliser toutes les émotions\n" .
              "Format: une phrase par ligne, chaque phrase commence par l'émoticône suivie d'un espace.\n\n" .
              "Exemples valides :\n" .
              "😊 Le petit lion était content d'aller au zoo.\n" .
              "😨 Mais il a eu peur quand le tigre a rugi.\n" .
              "🤢 En plus, l'odeur de poisson était dégoûtante.\n\n" .
              "😊 L'ourson était heureux de jouer dans le parc.\n" .
              "😢 Puis il a perdu son doudou, il était triste.\n" .
              "😊 Finalement, sa maman l'a retrouvé, quelle joie !\n\n" .
              "Retourne UNIQUEMENT l'histoire, 3 lignes, sans introduction ni explications.";
    
    return $this->callGroq($prompt);
}

/**
 * Generate emotion explanation for children
 * (Like your Java genererExplicationEmotion)
 */
public function genererExplicationEmotion(string $emotion): string
{
    $prompt = sprintf(
        "Explique simplement l'émotion '%s' pour un enfant autiste.\n" .
        "Utilise un langage simple et des exemples concrets.\n" .
        "Décris comment on reconnaît cette émotion sur le visage.\n" .
        "3-4 phrases maximum.\n" .
        "Retourne UNIQUEMENT l'explication, sans introduction.",
        $emotion
    );
    
    return $this->callGroq($prompt);
}
/**
 * Generate AI suggestions to improve an event for autism accessibility
 * 
 * @param array $eventData Event details (title, description, type, capacity, currentScore)
 * @return array List of suggestions with before/after, points gain, and explanation
 */
/**
 * Generate AI suggestions to improve an event for autism accessibility
 */
/**
 * Generate AI suggestions to improve an event for autism accessibility
 */
public function generateEventImprovementSuggestions(array $eventData): array
{
    try {
        $prompt = sprintf(
            "Tu es un expert en accessibilité pour personnes autistes. Analyse cet événement et propose 3 améliorations concrètes.\n\n" .
            "Événement:\n" .
            "- Titre: %s\n" .
            "- Type: %s\n" .
            "- Description: %s\n" .
            "- Capacité: %d personnes\n" .
            "- Score actuel d'accessibilité: %d%%\n\n" .
            "IMPORTANT - RÈGLES:\n" .
            "1. Pour le champ 'type', tu peux suggérer:\n" .
            "   - Soit un type EXISTANT parmi: Atelier, Conférence, Sortie, Formation\n" .
            "   - Soit un NOUVEAU type plus précis si pertinent (ex: 'Atelier sensoriel', 'Méditation guidée', 'Thérapie par l'art')\n" .
            "2. Si tu suggères un nouveau type, l'utilisateur sera invité à l'ajouter\n" .
            "3. Pour les autres champs (capacite, description, titre), donne des valeurs précises\n\n" .
            "Retourne UNIQUEMENT un objet JSON valide avec cette structure EXACTE:\n" .
            "{\n" .
            "  \"suggestions\": [\n" .
            "    {\n" .
            "      \"field\": \"capacite\",\n" .
            "      \"current\": \"200 personnes\",\n" .
            "      \"suggested\": \"30\",\n" .
            "      \"pointsGain\": 25,\n" .
            "      \"reason\": \"Les petits groupes réduisent l'anxiété sociale\",\n" .
            "      \"action\": \"Réduire la capacité à 30 personnes maximum\"\n" .
            "    },\n" .
            "    {\n" .
            "      \"field\": \"type\",\n" .
            "      \"current\": \"Concert\",\n" .
            "      \"suggested\": \"Atelier sensoriel\",\n" .
            "      \"pointsGain\": 35,\n" .
            "      \"reason\": \"Plus adapté aux besoins sensoriels\",\n" .
            "      \"action\": \"Changer le type en Atelier sensoriel\"\n" .
            "    }\n" .
            "  ],\n" .
            "  \"summary\": \"Résumé des améliorations possibles en une phrase\"\n" .
            "}\n\n" .
            "IMPORTANT: Retourne UNIQUEMENT le JSON, rien d'autre.",
            $eventData['title'],
            $eventData['type'],
            $eventData['description'],
            $eventData['capacity'],
            $eventData['currentScore']
        );
        
        $response = $this->callGroq($prompt);
        $cleanResponse = $this->nettoyerReponseJSON($response);
        
        $suggestions = json_decode($cleanResponse, true);
        
        if (isset($suggestions['suggestions'])) {
            return $suggestions;
        }
        
        return $this->getFallbackSuggestions($eventData);
        
    } catch (\Exception $e) {
        $this->logger->error('Groq suggestion error: ' . $e->getMessage());
        return $this->getFallbackSuggestions($eventData);
    }
}
/**
 * Generate a detailed analysis of an event with personalized advice
 */
public function generateEventAnalysis(array $eventData): string
{
    try {
        $prompt = sprintf(
            "Analyse cet événement pour une personne autiste et donne des conseils personnalisés.\n\n" .
            "Événement: %s\n" .
            "Type: %s\n" .
            "Description: %s\n" .
            "Capacité: %d personnes\n" .
            "Score actuel: %d%%\n\n" .
            "Réponds en français avec ce format:\n" .
            "🔍 CE QUI PEUT ÊTRE DIFFICILE:\n" .
            "- (point 1)\n" .
            "- (point 2)\n\n" .
            "💡 CONSEILS POUR S'ADAPTER:\n" .
            "- (conseil 1)\n" .
            "- (conseil 2)\n\n" .
            "✨ BON À SAVOIR:\n" .
            "- (information utile)\n\n" .
            "Sois bienveillant, concret et utile.",
            $eventData['title'],
            $eventData['type'],
            $eventData['description'],
            $eventData['capacity'],
            $eventData['currentScore']
        );
        
        $analysis = $this->callGroq($prompt);
        return $this->nettoyerReponse($analysis);
        
    } catch (\Exception $e) {
        return "🔍 Analyse non disponible pour le moment. Réessaie plus tard.";
    }
}

/**
 * Generate a personalized message for the user about event accessibility
 */
public function generateAccessibilityTip(array $eventData): string
{
    try {
        $prompt = sprintf(
            "En 1 phrase courte et positive, donne un conseil pour rendre cet événement plus accessible:\n\n" .
            "Titre: %s\n" .
            "Type: %s\n" .
            "Description: %s\n\n" .
            "Soit bienveillant et encourageant. Retourne UNIQUEMENT la phrase.",
            $eventData['title'],
            $eventData['type'],
            $eventData['description']
        );
        
        return $this->nettoyerReponse($this->callGroq($prompt));
        
    } catch (\Exception $e) {
        return "💡 Pense à vérifier l'environnement sonore et lumineux.";
    }
}

/**
 * Fallback suggestions when Groq API fails
 */
/**
 * Fallback suggestions when Groq API fails
 */
private function getFallbackSuggestions(array $eventData): array
{
    $suggestions = [];
    
    // Capacity suggestion
    if ($eventData['capacity'] > 30) {
        $suggestions[] = [
            'field' => 'capacite',
            'current' => $eventData['capacity'] . ' personnes',
            'suggested' => '30',
            'pointsGain' => 25,
            'reason' => 'Les petits groupes sont moins stressants',
            'action' => 'Réduire la capacité à 30 personnes maximum'
        ];
    }
    
    // Type suggestion - can suggest new types
    if ($eventData['type'] === 'Concert' || $eventData['type'] === 'Sortie') {
        $suggestions[] = [
            'field' => 'type',
            'current' => $eventData['type'],
            'suggested' => 'Atelier calme',
            'pointsGain' => 30,
            'reason' => 'Les ateliers sont plus structurés et prévisibles',
            'action' => 'Changer le type en Atelier calme (nouveau type)'
        ];
    }
    
    // Description suggestion
    if (strlen($eventData['description']) < 50) {
        $suggestions[] = [
            'field' => 'description',
            'current' => $eventData['description'],
            'suggested' => $eventData['description'] . ' Espace calme disponible. Casques anti-bruit sur demande.',
            'pointsGain' => 15,
            'reason' => 'Préparer les participants réduit l\'anxiété',
            'action' => 'Ajouter des détails sur les aménagements'
        ];
    }
    
    return [
        'suggestions' => $suggestions,
        'summary' => count($suggestions) . ' amélioration(s) possible(s) pour augmenter l\'accessibilité.'
    ];
}
/**
 * Génère des badges pour plusieurs événements en un seul appel
 */
public function generateEventsBadges(array $eventsData): array
{
    $prompt = "Analyse ces événements et génère pour CHACUN 3 badges (score IA, taille groupe, ambiance).\n\n";
    
    foreach ($eventsData as $i => $event) {
        $prompt .= sprintf(
            "Événement %d:\n- Titre: %s\n- Type: %s\n- Description: %s\n- Capacité: %d\n\n",
            $i + 1,
            $event['titre'],
            $event['type'],
            $event['description'],
            $event['capacity']
        );
    }
    
    $prompt .= "Retourne UNIQUEMENT un objet JSON avec cette structure:\n";
    $prompt .= "{\n  \"badges\": [\n";
    $prompt .= "    {\n";
    $prompt .= "      \"ia_score\": 92,\n";
    $prompt .= "      \"ia_level\": \"high\",\n";
    $prompt .= "      \"group_badge\": \"👥 Petit\",\n";
    $prompt .= "      \"ambiance_emoji\": \"🔇\",\n";
    $prompt .= "      \"ambiance_text\": \"Calme\"\n";
    $prompt .= "    }\n  ]\n}\n";
    $prompt .= "Les niveaux IA: 'high' (≥70), 'medium' (40-69), 'low' (<40)";
    
    $response = $this->callGroq($prompt);
    $cleanResponse = $this->nettoyerReponseJSON($response);
    $data = json_decode($cleanResponse, true);
    
    return $data['badges'] ?? [];
}
/**
 * Génère la liste "À apporter" personnalisée pour un événement
 */
/**
 * Génère la liste "À apporter" personnalisée pour un événement
 */
/**
 * Génère la liste "À apporter" personnalisée pour un événement (version courte)
 */
/**
 * Génère la liste "À apporter" personnalisée pour un événement (4-6 items, texte court)
 */
public function generateBringList(array $eventData): array
{
    try {
        $prompt = sprintf(
            "Donne une liste de 4 à 6 choses à apporter pour cet événement enfant autiste.\n\n" .
            "DÉTAILS DE L'ÉVÉNEMENT:\n" .
            "- Titre: %s\n" .
            "- Type: %s\n" .
            "- Lieu: %s\n" .
            "- Description: %s\n\n" .
            "RÈGLES:\n" .
            "1. Adapte-toi au LIEU:\n" .
            "   - Parc/extérieur → crème solaire, chapeau, veste\n" .
            "   - Salle/intérieur → chaussons, vêtement confortable\n" .
            "2. Adapte-toi à la DESCRIPTION:\n" .
            "   - Atelier créatif → tablier\n" .
            "   - Sport/actif → eau, vêtement de rechange\n" .
            "3. Texte COURT (max 5 mots après le tiret)\n" .
            "4. Retourne 4 à 6 items\n\n" .
            "Retourne UNIQUEMENT un JSON: {\"items\":[{\"emoji\":\"💧\",\"text\":\"Eau - rester hydraté\"}]}",
            $eventData['title'],
            $eventData['type'],
            $eventData['lieu'],
            $eventData['description']
        );
        
        $response = $this->callGroq($prompt);
        $cleanResponse = $this->nettoyerReponseJSON($response);
        $data = json_decode($cleanResponse, true);
        
        $items = $data['items'] ?? [];
        
        // S'assurer qu'on a entre 4 et 6 items
        if (count($items) < 4) {
            $items = array_merge($items, $this->getShortBringList($eventData));
            $items = array_slice($items, 0, 6);
        }
        if (count($items) > 6) {
            $items = array_slice($items, 0, 6);
        }
        
        return !empty($items) ? $items : $this->getShortBringList($eventData);
        
    } catch (\Exception $e) {
        $this->logger->error('Groq bring list error: ' . $e->getMessage());
        return $this->getShortBringList($eventData);
    }
}

/**
 * Liste courte par défaut (4-6 items)
 */
private function getShortBringList(array $eventData): array
{
    $items = [];
    $lieu = strtolower($eventData['lieu'] ?? '');
    $description = strtolower($eventData['description'] ?? '');
    $type = strtolower($eventData['type'] ?? '');
    
    // Items selon le lieu (texte court)
    if (strpos($lieu, 'parc') !== false || strpos($lieu, 'extérieur') !== false || strpos($lieu, 'jardin') !== false) {
        $items[] = ['emoji' => '🧴', 'text' => 'Crème solaire + chapeau'];
        $items[] = ['emoji' => '🧥', 'text' => 'Veste (prévoir frais)'];
        $items[] = ['emoji' => '💧', 'text' => 'Eau - rester hydraté'];
    } elseif (strpos($lieu, 'salle') !== false || strpos($lieu, 'intérieur') !== false) {
        $items[] = ['emoji' => '🩴', 'text' => 'Chaussons confortables'];
        $items[] = ['emoji' => '👕', 'text' => 'Vêtement de rechange'];
    }
    
    // Items selon le type d'activité
    if (strpos($type, 'atelier') !== false || strpos($description, 'peinture') !== false) {
        $items[] = ['emoji' => '🎨', 'text' => 'Tablier - atelier créatif'];
    }
    
    if (strpos($type, 'sport') !== false || strpos($description, 'actif') !== false) {
        $items[] = ['emoji' => '💧', 'text' => 'Eau + goûter'];
        $items[] = ['emoji' => '👕', 'text' => 'T-shirt de rechange'];
    }
    
    // Items par défaut (toujours utiles)
    $defaultItems = [
        ['emoji' => '💧', 'text' => 'Bouteille d\'eau'],
        ['emoji' => '🍎', 'text' => 'Petit goûter sain'],
        ['emoji' => '🧸', 'text' => 'Doudou ou jouet préféré'],
        ['emoji' => '👕', 'text' => 'Vêtements confortables']
    ];
    
    // Fusionner et garder 4-6 items
    $result = array_merge($items, $defaultItems);
    $result = array_slice($result, 0, 6);
    
    // S'assurer qu'on a au moins 4 items
    if (count($result) < 4) {
        $result = array_slice($defaultItems, 0, 5);
    }
    
    return $result;
}
private function getDefaultBringList(array $eventData): array
{
    $items = [
        ['emoji' => '💧', 'text' => 'Bouteille d\'eau'],
        ['emoji' => '🍎', 'text' => 'Petit goûter sain'],
        ['emoji' => '🧸', 'text' => 'Objet réconfortant (doudou, jouet)'],
        ['emoji' => '👕', 'text' => 'Vêtements confortables']
    ];
    
    // Ajouter des suggestions basées sur le type d'événement
    if (stripos($eventData['type'], 'atelier') !== false) {
        $items[] = ['emoji' => '🎨', 'text' => 'Tablier ou vêtements qui peuvent se salir'];
    }
    if (stripos($eventData['lieu'], 'extérieur') !== false || stripos($eventData['lieu'], 'parc') !== false) {
        $items[] = ['emoji' => '🧴', 'text' => 'Crème solaire et chapeau'];
        $items[] = ['emoji' => '🧥', 'text' => 'Veste ou pull (selon météo)'];
    }
    
    return array_slice($items, 0, 6);
}
/**
 * Génère les conseils personnalisés pour un événement
 */
/**
 * Génère les conseils personnalisés pour un événement
 */
public function generateTips(array $eventData): array
{
    try {
        $prompt = sprintf(
            "Tu es un expert en accompagnement d'enfants autistes.\n\n" .
            "Analyse cet événement et génère 4 conseils personnalisés pour les parents.\n\n" .
            "DÉTAILS DE L'ÉVÉNEMENT:\n" .
            "- Titre: %s\n" .
            "- Type: %s\n" .
            "- Description: %s\n" .
            "- Lieu: %s\n" .
            "- Capacité: %d personnes\n" .
            "- Score d'accessibilité: %d%%\n\n" .
            "INSTRUCTIONS:\n" .
            "1. Base tes conseils sur le TITRE, la DESCRIPTION et le LIEU spécifiques\n" .
            "2. Donne des conseils pratiques et réalistes\n" .
            "3. Anticipe les difficultés possibles liées à l'événement\n" .
            "4. Retourne UNIQUEMENT un objet JSON avec cette structure:\n" .
            "{\n" .
            "  \"tips\": [\n" .
            "    {\"emoji\": \"🕐\", \"text\": \"Arrivez 15 min avant - l'événement peut être bondé\"},\n" .
            "    {\"emoji\": \"🌿\", \"text\": \"Coin calme disponible à l'entrée\"}\n" .
            "  ]\n" .
            "}",
            $eventData['title'],
            $eventData['type'],
            $eventData['description'],
            $eventData['lieu'],
            $eventData['capacity'],
            $eventData['score']
        );
        
        $response = $this->callGroq($prompt);
        $cleanResponse = $this->nettoyerReponseJSON($response);
        $data = json_decode($cleanResponse, true);
        
        return $data['tips'] ?? $this->getDefaultTips($eventData);
        
    } catch (\Exception $e) {
        $this->logger->error('Groq tips error: ' . $e->getMessage());
        return $this->getDefaultTips($eventData);
    }
}

/**
 * Conseils par défaut (fallback)
 */
private function getDefaultTips(array $eventData): array
{
    $tips = [
        ['emoji' => '🕐', 'text' => 'Arrivez 15 minutes avant le début'],
        ['emoji' => '🌿', 'text' => 'Un coin calme sera disponible sur place'],
        ['emoji' => '💬', 'text' => 'N\'hésitez pas à nous parler des besoins spécifiques']
    ];
    
    // Ajouter des conseils basés sur la capacité
    if ($eventData['capacity'] > 50) {
        $tips[] = ['emoji' => '👥', 'text' => 'L\'événement peut être bondé - préparez votre enfant'];
    } else {
        $tips[] = ['emoji' => '👥', 'text' => 'Petit groupe - ambiance plus calme'];
    }
    
    // Ajouter des conseils basés sur le score
    if ($eventData['score'] < 50) {
        $tips[] = ['emoji' => '⚠️', 'text' => 'Environnement potentiellement stimulant - préparez des outils d\'apaisement'];
    }
    
    return $tips;
}
}