<?php

namespace App\Controller;

use App\Entity\Event;
use App\Service\EmotionDetectionService;
use App\Service\GroqService;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FaceEmotionController extends AbstractController
{
    private GroqService $groqService;
    
    public function __construct(GroqService $groqService)
    {
        $this->groqService = $groqService;
    }
    
    #[Route('/face-emotion/{idEvent}', name: 'app_face_emotion')]
    public function index(int $idEvent, EventRepository $eventRepository, SessionInterface $session): Response
    {
        $event = $eventRepository->find($idEvent);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }
        
     
        $session->clear();
        
       
        $session->set('emotion_event_id', $idEvent);
        
       
        $histoire = $this->groqService->genererHistoireCourte($event->getTitre());
        $histoire = $this->nettoyerHistoire($histoire);
        $histoires = [$histoire];
        
      
        $session->set('emotion_histoires', $histoires);
        $session->set('emotion_histoire_actuelle', 0);
        $session->set('emotion_etape_actuelle', 0);
        $session->set('emotion_score', 0);
        $session->set('emotion_etapes_validees', []);
        
       
        $etapes = $this->analyserEmotions($histoire);
        $session->set('emotion_etapes', $etapes);
        
        return $this->render('front/events/face_emotion.html.twig', [
            'event' => $event,
            'histoire_actuelle' => 0,
            'total_histoires' => 1, // ✅ 1 seule histoire
            'etape_actuelle' => 0,
            'etapes' => $etapes,
            'total_etapes' => count($etapes),
            'score' => 0
        ]);
    }
    
    #[Route('/api/emotion/new-story', name: 'api_emotion_new_story', methods: ['POST'])]
    public function newStory(SessionInterface $session, EventRepository $eventRepository): JsonResponse
    {
        // ✅ RÉCUPÉRER L'ID DEPUIS LA SESSION (maintenant il existe)
        $eventId = $session->get('emotion_event_id');
        
        if (!$eventId) {
            return $this->json(['success' => false, 'message' => 'ID événement non trouvé'], 400);
        }
        
        $event = $eventRepository->find($eventId);
        
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Événement non trouvé'], 404);
        }
        
        $nouvelleHistoire = $this->groqService->genererHistoireCourte($event->getTitre());
        $nouvelleHistoire = $this->nettoyerHistoire($nouvelleHistoire);
        $nouvellesEtapes = $this->analyserEmotions($nouvelleHistoire);
        
        $session->set('emotion_histoires', [$nouvelleHistoire]);
        $session->set('emotion_etapes', $nouvellesEtapes);
        $session->set('emotion_etape_actuelle', 0);
        $session->set('emotion_score', 0);
        $session->set('emotion_etapes_validees', []);
        
        return $this->json([
            'success' => true,
            'etapes' => $nouvellesEtapes,
            'total_etapes' => count($nouvellesEtapes),
            'premiere_etape' => [
                'texte' => $nouvellesEtapes[0]['texte'],
                'emotion' => $nouvellesEtapes[0]['emotion'],
                'icone' => $this->getEmotionIcone($nouvellesEtapes[0]['emotion'])
            ]
        ]);
    }
    
    #[Route('/api/emotion/detect-live', name: 'api_emotion_detect_live', methods: ['POST'])]
    public function detectLive(Request $request, EmotionDetectionService $emotionService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $base64Image = $data['image'] ?? null;
        
        if (!$base64Image) {
            return $this->json(['success' => false, 'error' => 'No image'], 400);
        }
        
        $result = $emotionService->detectEmotionFull($base64Image);
        
        return $this->json([
            'success' => true,
            'emotion' => $result['emotion'],
            'confidence' => $result['confidence'],
            'face_detected' => $result['face_detected'],
            'face_rect' => $result['face_rect'] ?? null
        ]);
    }
    
    #[Route('/api/emotion/capture', name: 'api_emotion_capture', methods: ['POST'])]
    public function captureEmotion(Request $request, EmotionDetectionService $emotionService, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $base64Image = $data['image'] ?? null;
        
        if (!$base64Image) {
            return $this->json(['success' => false, 'message' => 'Aucune image'], 400);
        }
        
        // État du jeu
        $histoireActuelle = $session->get('emotion_histoire_actuelle', 0);
        $etapeActuelle = $session->get('emotion_etape_actuelle', 0);
        $etapes = $session->get('emotion_etapes', []);
        $score = $session->get('emotion_score', 0);
        $etapesValidees = $session->get('emotion_etapes_validees', []);
        
       
        if ($histoireActuelle >= 1) {
            return $this->json([
                'success' => true,
                'game_finished' => true,
                'score' => $score
            ]);
        }
        
        // Vérifier si déjà validé
        $etapeId = $histoireActuelle . '_' . $etapeActuelle;
        if (in_array($etapeId, $etapesValidees)) {
            return $this->json([
                'success' => true,
                'correct' => false,
                'already_validated' => true,
                'message' => '✅ Déjà validé ! Passe à la suite.'
            ]);
        }
        
        // Détecter l'émotion
        $detection = $emotionService->detectEmotionFull($base64Image);
        
        if (!$detection['face_detected']) {
            return $this->json([
                'success' => false,
                'message' => '👤 Aucun visage détecté'
            ]);
        }
        
        $emotionDetectee = $detection['emotion'];
        $emotionAttendue = $etapes[$etapeActuelle]['emotion'];
        
        // Normaliser pour comparaison
        $emotionDetecteeNorm = $this->normaliserEmotion($emotionDetectee);
        $emotionAttendueNorm = $this->normaliserEmotion($emotionAttendue);
        
        if ($emotionDetecteeNorm === $emotionAttendueNorm) {
            // Bonne émotion
            $newScore = $score + 1;
            $etapesValidees[] = $etapeId;
            
            $session->set('emotion_score', $newScore);
            $session->set('emotion_etapes_validees', $etapesValidees);
            
            // Générer explication
            $explication = $this->genererExplicationEmotion($emotionAttendue);
            
            return $this->json([
                'success' => true,
                'correct' => true,
                'score' => $newScore,
                'message' => '🎉 Bravo ! C\'est bien de la ' . $emotionAttendue . ' !',
                'emotion_attendue' => $emotionAttendue,
                'explication' => $explication,
                'icone' => $this->getEmotionIcone($emotionAttendue),
                'couleur' => $this->getEmotionCouleur($emotionAttendue),
                'face_rect' => $detection['face_rect'] ?? null
            ]);
        }
        
        return $this->json([
            'success' => true,
            'correct' => false,
            'message' => '😅 Essaie encore ! Fais une expression de ' . $emotionAttendue,
            'emotion_detectee' => $emotionDetectee,
            'emotion_attendue' => $emotionAttendue
        ]);
    }
    
    #[Route('/api/emotion/next', name: 'api_emotion_next', methods: ['POST'])]
    public function nextStep(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $nouvelleHistoire = $data['nouvelle_histoire'] ?? false;
        
        $histoireActuelle = $session->get('emotion_histoire_actuelle', 0);
        $etapeActuelle = $session->get('emotion_etape_actuelle', 0);
        $etapes = $session->get('emotion_etapes', []);
        $histoires = $session->get('emotion_histoires', []);
        
        if ($nouvelleHistoire) {
            // ✅ Passer à l'histoire suivante (mais on n'a qu'une seule histoire)
            $histoireActuelle++;
            
            // ✅ Fin du jeu si plus d'histoire
            if ($histoireActuelle >= 1) {
                return $this->json([
                    'success' => true,
                    'game_finished' => true,
                    'score' => $session->get('emotion_score', 0)
                ]);
            }
            
            $nouvellesEtapes = $this->analyserEmotions($histoires[$histoireActuelle]);
            $session->set('emotion_histoire_actuelle', $histoireActuelle);
            $session->set('emotion_etapes', $nouvellesEtapes);
            $session->set('emotion_etape_actuelle', 0);
            
            return $this->json([
                'success' => true,
                'histoire_actuelle' => $histoireActuelle,
                'etape_actuelle' => 0,
                'etapes' => $nouvellesEtapes,
                'texte' => $nouvellesEtapes[0]['texte'],
                'emotion_attendue' => $nouvellesEtapes[0]['emotion'],
                'icone' => $this->getEmotionIcone($nouvellesEtapes[0]['emotion'])
            ]);
        } else {
            // Étape suivante dans la même histoire
            $etapeActuelle++;
            
            if ($etapeActuelle >= count($etapes)) {
                return $this->json([
                    'success' => true,
                    'histoire_terminee' => true
                ]);
            }
            
            $session->set('emotion_etape_actuelle', $etapeActuelle);
            
            return $this->json([
                'success' => true,
                'etape_actuelle' => $etapeActuelle,
                'texte' => $etapes[$etapeActuelle]['texte'],
                'emotion_attendue' => $etapes[$etapeActuelle]['emotion'],
                'icone' => $this->getEmotionIcone($etapes[$etapeActuelle]['emotion'])
            ]);
        }
    }
    
    #[Route('/api/emotion/previous', name: 'api_emotion_previous', methods: ['POST'])]
    public function previousStep(SessionInterface $session): JsonResponse
    {
        $etapeActuelle = $session->get('emotion_etape_actuelle', 0);
        
        if ($etapeActuelle > 0) {
            $nouvelleEtape = $etapeActuelle - 1;
            $session->set('emotion_etape_actuelle', $nouvelleEtape);
            $etapes = $session->get('emotion_etapes', []);
            
            return $this->json([
                'success' => true,
                'etape_actuelle' => $nouvelleEtape,
                'texte' => $etapes[$nouvelleEtape]['texte'],
                'emotion_attendue' => $etapes[$nouvelleEtape]['emotion'],
                'icone' => $this->getEmotionIcone($etapes[$nouvelleEtape]['emotion'])
            ]);
        }
        
        return $this->json(['success' => false]);
    }
    
    #[Route('/api/emotion/reset', name: 'api_emotion_reset', methods: ['POST'])]
    public function resetGame(SessionInterface $session): JsonResponse
    {
        $session->clear();
        return $this->json(['success' => true]);
    }
    
    // ========== MÉTHODES PRIVÉES ==========
    
   private function analyserEmotions(string $histoire): array
{
    $lignes = explode("\n", $histoire);
    $etapes = [];
    
    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);
        if (empty($ligne)) continue;
        
        $emotion = '';
        $texte = $ligne;
        
        if (str_contains($ligne, '😊')) {
            $emotion = 'Joie';
            $texte = str_replace('😊', '', $texte);
        } elseif (str_contains($ligne, '😢')) {
            $emotion = 'Tristesse';
            $texte = str_replace('😢', '', $texte);
        } elseif (str_contains($ligne, '😲')) {
            $emotion = 'Surprise';
            $texte = str_replace('😲', '', $texte);
        } elseif (str_contains($ligne, '😠')) {
            $emotion = 'Colère';
            $texte = str_replace('😠', '', $texte);
        } elseif (str_contains($ligne, '😨')) {  // ← AJOUTÉ
            $emotion = 'Peur';
            $texte = str_replace('😨', '', $texte);
        } elseif (str_contains($ligne, '🤢')) {  // ← AJOUTÉ
            $emotion = 'Dégout';
            $texte = str_replace('🤢', '', $texte);
        } else {
            continue;
        }
        
        $etapes[] = [
            'texte' => trim($texte),
            'emotion' => $emotion,
            'icone' => $this->getEmotionIcone($emotion)
        ];
    }
    
    if (empty($etapes)) {
        $etapes = [
            ['texte' => 'Commençons notre aventure!', 'emotion' => 'Joie', 'icone' => '😊'],
            ['texte' => 'Une surprise nous attend!', 'emotion' => 'Surprise', 'icone' => '😲'],
            ['texte' => 'Tout est bien qui finit bien!', 'emotion' => 'Joie', 'icone' => '😊']
        ];
    }
    
    return $etapes;
}
    
    private function nettoyerHistoire(string $histoire): string
    {
        $histoire = preg_replace('/^voici.*histoire.*:\s*/i', '', $histoire);
        $histoire = preg_replace('/^d\'accord.*:\s*/i', '', $histoire);
        $histoire = preg_replace('/\*\*/', '', $histoire);
        return trim($histoire);
    }
    
   private function normaliserEmotion(string $emotion): string
{
    $map = [
        'Joie' => 'Joie', 'joy' => 'Joie', 'happy' => 'Joie',
        'Tristesse' => 'Tristesse', 'sad' => 'Tristesse',
        'Colère' => 'Colère', 'anger' => 'Colère', 'angry' => 'Colère',
        'Surprise' => 'Surprise', 'surprise' => 'Surprise',
        'Peur' => 'Peur', 'fear' => 'Peur', 'scared' => 'Peur',  // ← AJOUTÉ
        'Dégout' => 'Dégout', 'disgust' => 'Dégout', 'disgusted' => 'Dégout'  // ← AJOUTÉ
    ];
    return $map[$emotion] ?? $emotion;
}
    private function getEmotionIcone(string $emotion): string
{
    return match($emotion) {
        'Joie' => '😊',
        'Tristesse' => '😢',
        'Colère' => '😠',
        'Surprise' => '😲',
        'Peur' => '😨',      // ← AJOUTÉ
        'Dégout' => '🤢',    // ← AJOUTÉ
        default => '😐'
    };
}
    
   private function getEmotionCouleur(string $emotion): string
{
    return match($emotion) {
        'Joie' => '#FBBF24',
        'Tristesse' => '#3B82F6',
        'Colère' => '#EF4444',
        'Surprise' => '#A855F7',
        'Peur' => '#D946EF',     // ← AJOUTÉ (violet/magenta)
        'Dégout' => '#6B7280',   // ← AJOUTÉ (gris)
        default => '#7B2FF7'
    };
}
    
  private function genererExplicationEmotion(string $emotion): string
{
    try {
        return $this->groqService->genererExplicationEmotion($emotion);
    } catch (\Exception $e) {
        $explanations = [
            'Joie' => 'La joie, c\'est quand tu es content et que tu as envie de sourire ! 🌟',
            'Tristesse' => 'La tristesse, c\'est normal. Un câlin peut aider à se sentir mieux. 🤗',
            'Colère' => 'La colère, c\'est quand tu es énervé. Respire profondément ! 😤➡️😌',
            'Surprise' => 'La surprise, c\'est quand quelque chose d\'inattendu arrive ! 🎁',
            'Peur' => 'La peur, c\'est quand tu te sens menacé. Respire et souviens-toi que tu es en sécurité ! 🛡️',  // ← AJOUTÉ
            'Dégout' => 'Le dégoût, c\'est quand quelque chose te semble désagréable. C\'est normal de ne pas aimer certaines choses ! 🍃'  // ← AJOUTÉ
        ];
        return $explanations[$emotion] ?? 'Cette émotion est importante à reconnaître. 💛';
    }
}
    
   private function getFallbackHistoire(int $num, string $eventTitle): string
{
    $histoires = [
        "😊 Le petit lion participe à $eventTitle.\n😨 Il a peur du bruit.\n🤢 Une odeur bizarre le dégoûte.\n😊 Puis tout va bien !",
        "😊 Le petit ours va à $eventTitle.\n😠 Un renard lui prend sa place.\n😲 Un papillon magique l'aide.\n😊 La journée se termine bien.",
        "😊 Le petit lapin est invité à $eventTitle.\n😢 Il se sent seul.\n😨 Il a peur de ne pas y arriver.\n😊 Il rencontre de nouveaux amis."
    ];
    return $histoires[$num - 1] ?? $histoires[0];
}
}