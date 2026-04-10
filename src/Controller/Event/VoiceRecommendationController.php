<?php

namespace App\Controller\Event;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\AssemblyAiService;
use App\Service\GroqService;
use App\Service\VoiceRecommendationService;
use Psr\Log\LoggerInterface;

class VoiceRecommendationController extends AbstractController
{
    private VoiceRecommendationService $voiceService;
    private LoggerInterface $logger;

    public function __construct(
        VoiceRecommendationService $voiceService,
        LoggerInterface $logger
    ) {
        $this->voiceService = $voiceService;
        $this->logger = $logger;
    }

    #[Route('/voice-assistant', name: 'voice_assistant')]
    public function index(): Response
    {
        return $this->render('front/events/voice_recommendation.html.twig');
    }

    #[Route('/api/voice/recommend', name: 'api_voice_recommend', methods: ['POST'])]
    public function recommend(Request $request): JsonResponse
    {
        $tempPath = null;
        
        try {
            $this->logger->info('=== API RECOMMEND START ===');
            
            $audioFile = $request->files->get('audio');
            
            if (!$audioFile) {
                return $this->json(['success' => false, 'error' => 'Aucun fichier audio reçu'], 400);
            }
            
            // Sauvegarder le fichier temporaire
            $originalExtension = $audioFile->getClientOriginalExtension();
            $tempPath = sys_get_temp_dir() . '/voice_' . uniqid() . '.' . $originalExtension;
            $audioFile->move(sys_get_temp_dir(), basename($tempPath));
            
            $selectedEmotion = $request->request->get('selected_emotion');
            
            // Appeler le service
            $result = $this->voiceService->analyzeAndRecommend($tempPath, $selectedEmotion);
            
            // Nettoyer
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'user_text' => $result['user_text'],
                    'emotion' => $this->traduireEmotionPourAffichage($result['emotion']),
                    'emotion_icon' => $this->getEmotionIcon($result['emotion']),
                    'confidence' => $result['confidence'] ?? 0.8,
                    'advice' => $result['advice'],
                    'recommended_events' => $result['recommended_events'],
                    'fallback_used' => false
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur inconnue'
                ], 500);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur voice recommendation: ' . $e->getMessage());
            
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            return $this->getFallbackResponse($request);
        }
    }

    private function getFallbackResponse(Request $request): JsonResponse
    {
        $selectedEmotion = $request->request->get('selected_emotion', 'neutre');
        
        $adviceByEmotion = [
            'joyeux' => 'Continue à rayonner ! Ta joie est contagieuse. 🌟',
            'triste' => 'C\'est okay d\'être triste. Un câlin virtuel pour toi 🤗',
            'colere' => 'Ta colère est légitime. Respire profondément. 🎨',
            'peur' => 'La peur nous protège. Tu es plus fort(e) que tes peurs. 💪',
            'neutre' => 'C\'est une belle journée pour prendre soin de toi. 🌸'
        ];
        
        $advice = $adviceByEmotion[$selectedEmotion] ?? $adviceByEmotion['neutre'];
        
        $emotionDisplay = [
            'joyeux' => '😊 Joie',
            'triste' => '😢 Tristesse',
            'colere' => '😠 Colère',
            'peur' => '😰 Anxiété',
            'neutre' => '😐 Neutre'
        ];
        
        return $this->json([
            'success' => true,
            'user_text' => $request->request->get('selected_emotion') ? 
                "Je me sens " . $selectedEmotion : 
                "Je cherche à comprendre mes émotions",
            'emotion' => $emotionDisplay[$selectedEmotion] ?? '😐 Neutre',
            'emotion_icon' => $this->getEmotionIcon($selectedEmotion),
            'confidence' => 0.9,
            'advice' => $advice,
            'recommended_events' => [
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
                    'description' => 'Partage tes émotions',
                    'emoji' => '💬',
                    'participants' => 0,
                    'score' => 82,
                    'raison' => 'Exprime ce que tu ressens'
                ]
            ],
            'fallback_used' => true
        ]);
    }

    private function traduireEmotionPourAffichage(string $emotion): string
    {
        $map = [
            'joyeux' => '😊 Joie',
            'triste' => '😢 Tristesse',
            'colere' => '😠 Colère',
            'peur' => '😰 Anxiété',
            'neutre' => '😐 Neutre'
        ];
        return $map[$emotion] ?? '🤔 ' . $emotion;
    }

    private function getEmotionIcon(string $emotion): string
    {
        $map = [
            'joyeux' => '😊',
            'triste' => '😢',
            'colere' => '😠',
            'peur' => '😰',
            'neutre' => '😐'
        ];
        return $map[$emotion] ?? '🤔';
    }
}