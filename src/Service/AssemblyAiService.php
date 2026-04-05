<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AssemblyAiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $apiUrl;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $apiKey,
        string $apiUrl
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');
    }

    /**
     * Transcrit un fichier audio
     */
    public function transcribe(string $audioPath): array
    {
        try {
            $this->logger->info('=== Starting transcription ===');
            
            // 1. Upload du fichier audio
            $uploadUrl = $this->uploadAudio($audioPath);
            $this->logger->info('Upload URL: ' . $uploadUrl);
            
            // 2. Lancer la transcription
            $transcriptId = $this->startTranscription($uploadUrl);
            $this->logger->info('Transcript ID: ' . $transcriptId);
            
            // 3. Attendre le résultat
            $result = $this->waitForResult($transcriptId);
            
            return [
                'text' => $result['text'] ?? '',
                'confidence' => $result['confidence'] ?? 0
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Transcription error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload du fichier audio vers AssemblyAI
     */
    private function uploadAudio(string $audioPath): string
    {
        // ✅ Vérifier que le fichier existe
        if (!file_exists($audioPath)) {
            throw new \Exception('File not found: ' . $audioPath);
        }
        
        // ✅ Vérifier que le fichier n'est pas vide
        $fileSize = filesize($audioPath);
        if ($fileSize === 0) {
            throw new \Exception('File is empty: ' . $audioPath);
        }
        
        $this->logger->info('Uploading file: ' . $audioPath . ' (' . $fileSize . ' bytes)');
        
        $audioContent = file_get_contents($audioPath);
        
        $response = $this->httpClient->request('POST', $this->apiUrl . '/upload', [
            'headers' => [
                'authorization' => $this->apiKey,
                'content-type' => 'application/octet-stream',
            ],
            'body' => $audioContent,
        ]);
        
        // ✅ toArray(false) pour voir l'erreur réelle
        $data = $response->toArray(false);
        
        $this->logger->info('Upload response: ' . json_encode($data));
        
        if (!isset($data['upload_url'])) {
            throw new \Exception('Upload failed: ' . json_encode($data));
        }
        
        return $data['upload_url'];
    }

    /**
     * Démarre la transcription AssemblyAI
     */
    private function startTranscription(string $audioUrl): string
    {
        // ✅ Vérifier que l'URL n'est pas vide
        if (empty($audioUrl)) {
            throw new \Exception('Audio URL is empty');
        }
        
        // ✅ Vérifier que c'est une URL valide
        if (!filter_var($audioUrl, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid audio URL: ' . $audioUrl);
        }
        
        $this->logger->info('Starting transcription for URL: ' . $audioUrl);
        
        $response = $this->httpClient->request('POST', $this->apiUrl . '/transcript', [
            'headers' => [
                'authorization' => $this->apiKey,
                'content-type' => 'application/json',
            ],
            'json' => [
                'audio_url' => $audioUrl,
                'speech_models' => ['universal-2'],  // ✅ Tableau avec crochets
                'language_code' => 'fr',
            ],
        ]);
        
        // ✅ toArray(false) pour voir l'erreur réelle
        $data = $response->toArray(false);
        
        $this->logger->info('Transcription response: ' . json_encode($data));
        
        if (!isset($data['id'])) {
            throw new \Exception('Transcription failed: ' . json_encode($data));
        }
        
        return $data['id'];
    }

    /**
     * Attend le résultat de la transcription
     */
    private function waitForResult(string $transcriptId): array
    {
        $maxAttempts = 30;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            $response = $this->httpClient->request('GET', $this->apiUrl . '/transcript/' . $transcriptId, [
                'headers' => [
                    'authorization' => $this->apiKey,
                ],
            ]);
            
            $data = $response->toArray(false);
            
            $this->logger->info('Polling attempt ' . ($attempt + 1) . ': ' . ($data['status'] ?? 'unknown'));
            
            if ($data['status'] === 'completed') {
                return $data;
            }
            
            if ($data['status'] === 'error') {
                throw new \Exception('Transcription error: ' . ($data['error'] ?? 'Unknown'));
            }
            
            sleep(1);
            $attempt++;
        }
        
        throw new \Exception('Transcription timeout after ' . $maxAttempts . ' seconds');
    }
}