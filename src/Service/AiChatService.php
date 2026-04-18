<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;

class AiChatService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;

    /**
     * Models tried in order.
     * gemini-1.5-flash is the standard for free-tier/low-quota regions.
     */
    private array $models = [
        'google/gemini-2.0-flash-001',
        'meta-llama/llama-3-8b-instruct',
    ];

    /** Maximum retries per model */
    private int $maxRetriesPerModel = 2;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        #[Autowire('%env(OPENROUTER_API_KEY)%')] string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = trim($apiKey);
    }

    public function getAiResponse(string $userMessage, string $context): string
    {
        if ($this->apiKey === 'VOTRE_CLE_OU_ICI' || empty($this->apiKey)) {
            return "Désolé, la clé API OpenRouter n'est pas configurée. Veuillez l'ajouter dans le fichier .env.";
        }

        // Reduced prompt length to lower resource usage
        $fullPrompt = "Tu es AutiCare Guardian, un assistant pour parents d'enfants autistes.\n" .
                      "Données enfant :\n{$context}\n" .
                      "Message parent : {$userMessage}\n" .
                      "Réponds brièvement, avec empathie, sans conseil médical.";

        $lastError = '';
        $fallbackResponse = $this->buildFallbackResponse($userMessage, $context);
        $creditUnavailableResponse = "Le service IA est momentanément indisponible car le compte OpenRouter n'a plus de crédits. Je peux t'aider sur un rendez-vous ou une séance.";

        foreach ($this->models as $model) {
            $url = "https://openrouter.ai/api/v1/chat/completions";

            for ($attempt = 0; $attempt <= $this->maxRetriesPerModel; $attempt++) {
                if ($attempt > 0) {
                    $this->logger->warning("Gemini Rate Limit/Busy. Retry " . ($attempt + 1) . "... (Model: {$model})");
                    // Longer delay for 429/503: 3s, 6s
                    sleep($attempt * 3); 
                }

                try {
                    $response = $this->httpClient->request('POST', $url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'HTTP-Referer' => 'https://auticare.tn', // Site name for OpenRouter
                            'X-Title' => 'AutiCare',
                            'Content-Type' => 'application/json'
                        ],
                        'timeout' => 40,
                        'json' => [
                            'model' => $model,
                            'messages' => [
                                ['role' => 'user', 'content' => $fullPrompt]
                            ],
                            'temperature' => 0.7,
                            'max_tokens' => 600
                        ]
                    ]);

                    $statusCode = $response->getStatusCode();

                    if (in_array($statusCode, [401, 402], true)) {
                        $body = $response->getContent(false);
                        $data = json_decode($body, true);
                        $lastError = is_array($data) ? ($data['error']['message'] ?? 'Accès OpenRouter refusé') : 'Accès OpenRouter refusé';

                        if ($statusCode === 402 || stripos($lastError, 'Insufficient credits') !== false) {
                            $this->logger->warning('OpenRouter sans crédits: réponse dédiée envoyée.');
                            return $creditUnavailableResponse;
                        }

                        $this->logger->warning('OpenRouter refusé: réponse de secours utilisée. ' . $lastError);
                        return $fallbackResponse;
                    }

                    if ($statusCode === 429) {
                        $lastError = "Limite de requêtes atteinte (OpenRouter Quotas)";
                        continue;
                    }

                    if (in_array($statusCode, [503, 500])) {
                        $lastError = "Serveur saturé (HTTP {$statusCode})";
                        continue; // Try again after sleep
                    }

                    $data = $response->toArray(false);

                    if (isset($data['error'])) {
                        $lastError = $data['error']['message'] ?? 'Erreur API';
                        if ($statusCode === 404) break; // Model not available
                        continue;
                    }

                    return $data['choices'][0]['message']['content']
                        ?? "L'IA est momentanément confuse. Veuillez reformuler.";

                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    continue; 
                }
            }
        }

        $this->logger->warning('OpenRouter indisponible, réponse de secours utilisée: ' . $lastError);
        return $fallbackResponse;
    }

    private function buildFallbackResponse(string $userMessage, string $context): string
    {
        $message = mb_strtolower(trim($userMessage));

        if ($message === '') {
            return "Je suis disponible pour aider sur les rendez-vous, séances et informations de suivi.";
        }

        if (str_contains($message, 'rdv') || str_contains($message, 'rendez') || str_contains($message, 'rendez-vous')) {
            return "Je ne peux pas consulter l'IA en ce moment, mais je peux t'aider à vérifier les rendez-vous ou à reformuler ta demande.";
        }

        if (str_contains($message, 'séance') || str_contains($message, 'seance')) {
            return "Je ne peux pas interroger l'IA pour l'instant. Si tu veux, je peux t'aider à retrouver les séances ou leur état.";
        }

        if (str_contains($message, 'bonjour') || str_contains($message, 'salut')) {
            return "Bonjour. Le service IA est momentanément indisponible, mais je peux t'aider sur les rendez-vous et les séances.";
        }

        return "Le service IA est momentanément indisponible. Réessaie dans un instant ou pose une question sur un rendez-vous ou une séance.";
    }
}




