<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class StabilityImageService
{
    private const API_URL = "https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image";
    
    private $httpClient;
    private $logger;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $apiKey = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey ?? $_ENV['STABILITY_API_KEY'] ?? '';
    }

    /**
     * Génère une image avec Stability AI
     * @param string $prompt La description de l'image
     * @return string|null Les bytes de l'image en base64 ou null
     */
    public function genererImage(string $prompt): ?string
    {
        try {
            $this->logger->info('🎨 Stability AI - Génération pour: "' . $prompt . '"');

            $payload = [
                'text_prompts' => [
                    [
                        'text' => $prompt . ", children's drawing style, cartoon, colorful, simple, white background, educational for kids",
                        'weight' => 1
                    ]
                ],
                'cfg_scale' => 7,
                'height' => 1024,
                'width' => 1024,
                'samples' => 1,
                'steps' => 30
            ];

            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'json' => $payload,
                'timeout' => 60
            ]);

            if ($response->getStatusCode() !== 200) {
                $errorBody = $response->getContent(false);
                $this->logger->error('❌ Erreur API Stability: ' . $response->getStatusCode());
                $this->logger->error('Détails: ' . $errorBody);
                return null;
            }

            $data = $response->toArray();
            
            if (isset($data['artifacts'][0]['base64'])) {
                $this->logger->info('✅ Image générée avec succès');
                return $data['artifacts'][0]['base64'];
            }
            
            $this->logger->error('❌ Pas d\'image dans la réponse');
            return null;

        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur génération image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Version simplifiée avec le modèle plus rapide
     */
    public function genererImageRapide(string $prompt): ?string
    {
        try {
            $url = "https://api.stability.ai/v1/generation/stable-diffusion-v1-6/text-to-image";
            
            $payload = [
                'text_prompts' => [
                    [
                        'text' => $prompt . ", cartoon, colorful",
                        'weight' => 1
                    ]
                ],
                'cfg_scale' => 7,
                'height' => 512,
                'width' => 512,
                'samples' => 1,
                'steps' => 20
            ];

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload,
                'timeout' => 45
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                if (isset($data['artifacts'][0]['base64'])) {
                    return $data['artifacts'][0]['base64'];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('❌ Erreur génération rapide: ' . $e->getMessage());
        }
        return null;
    }
}