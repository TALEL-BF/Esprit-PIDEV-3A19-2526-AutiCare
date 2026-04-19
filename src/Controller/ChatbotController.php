<?php

namespace App\Controller;

use App\Repository\SuivieEntityRepository;
use App\Services\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    public function __construct(
        private GeminiService $geminiService
    ) {}

    // ── Endpoint chatbot parent ──────────────────────────────────
    #[Route('/chatbot/ask', name: 'chatbot_ask', methods: ['POST'])]
    public function ask(
        Request                $request,
        SuivieEntityRepository $repo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $question  = trim($data['question'] ?? '');
        $nomEnfant = trim($data['nomEnfant'] ?? '');
        $historique = $data['historique'] ?? [];

        if (!$question) {
            return $this->json(['error' => 'Question manquante'], 400);
        }

        // Limiter l'historique aux 6 derniers messages pour ne pas dépasser le contexte
        $historique = array_slice($historique, -6);

        // Récupérer le profil de l'enfant si un nom est fourni
        $age          = 0;
        $avgH         = 0.0;
        $avgS         = 0.0;
        $avgA         = 0.0;
        $nomTherapie  = '';

        if ($nomEnfant) {
            $sessions = $repo->findBy(['nomEnfant' => $nomEnfant], ['dateSuivie' => 'ASC']);
            if (!empty($sessions)) {
                $nb   = count($sessions);
                $last = end($sessions);
                $age  = $last->getAge() ?? 0;
                $avgH = round(array_sum(array_map(fn($s) => $s->getScoreHumeur()    ?? 0, $sessions)) / $nb, 1);
                $avgS = round(array_sum(array_map(fn($s) => $s->getScoreStress()    ?? 0, $sessions)) / $nb, 1);
                $avgA = round(array_sum(array_map(fn($s) => $s->getScoreAttention() ?? 0, $sessions)) / $nb, 1);
                $nomTherapie = $last->getTherapie()?->getNomExercice() ?? '';
            }
        }

        $reponse = $this->geminiService->chatbotParent(
            question:       $question,
            nomEnfant:      $nomEnfant,
            age:            $age,
            avgHumeur:      $avgH,
            avgStress:      $avgS,
            avgAttention:   $avgA,
            nomTherapie:    $nomTherapie,
            historique:     $historique
        );

        return $this->json(['reponse' => $reponse]);
    }
}