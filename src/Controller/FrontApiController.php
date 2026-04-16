<?php

namespace App\Controller;

use App\Repository\SuiviTotalRepository;
use App\Service\GameCatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class FrontApiController extends AbstractController
{
    #[Route('/api/front/recommended-games', name: 'api_front_recommended_games', methods: ['GET'])]
    public function recommendedGames(
        Request $request,
        SuiviTotalRepository $suiviTotalRepository,
        GameCatalogService $catalog
    ): JsonResponse {
        $enfantId = (int) $request->query->get('enfantId', 0);
        if ($enfantId <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Le parametre enfantId est obligatoire.',
            ], 400);
        }

        $suivi = $suiviTotalRepository->findLastByEnfantId($enfantId);
        if ($suivi === null || $suivi->getMoyenne() === null) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun suivi total trouve pour cet enfant.',
            ], 404);
        }

        $moyenne = (float) $suivi->getMoyenne();
        $level = $this->resolveLevelByAverage($moyenne);

        return $this->json([
            'success' => true,
            'enfantId' => $enfantId,
            'moyenne' => $moyenne,
            'niveau' => $level,
            'games' => $catalog->gamesForLevel($level),
        ]);
    }

    #[Route('/api/chatbot/ask', name: 'api_chatbot_ask', methods: ['GET', 'POST'])]
    public function askChatbot(Request $request, SuiviTotalRepository $suiviTotalRepository): JsonResponse
    {
        if ($request->isMethod('GET')) {
            $message = trim((string) $request->query->get('message', ''));
            $enfantId = (int) $request->query->get('enfantId', 0);
        } else {
            $payload = $request->toArray();
            $message = trim((string) ($payload['message'] ?? ''));
            $enfantId = (int) ($payload['enfantId'] ?? 0);
        }

        if ($message == '') {
            if ($request->isMethod('GET')) {
                return $this->json([
                    'success' => true,
                    'message' => 'API chatbot operationnelle. Utilisez ?message=...&enfantId=... pour interroger le bot.',
                    'example' => '/api/chatbot/ask?message=quel+est+mon+niveau&enfantId=1',
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => 'Le message est obligatoire.',
            ], 400);
        }

        $response = 'Je suis AutiBot. Je peux te conseiller un jeu, te donner un defi, ou expliquer une regle.';

        if (str_contains(mb_strtolower($message), 'niveau') && $enfantId > 0) {
            $suivi = $suiviTotalRepository->findLastByEnfantId($enfantId);
            if ($suivi !== null && $suivi->getMoyenne() !== null) {
                $moyenne = (float) $suivi->getMoyenne();
                $level = $this->resolveLevelByAverage($moyenne);
                $response = sprintf(
                    'Ton niveau recommande est %s avec une moyenne de %.2f/20. Commence par les jeux de ce niveau puis augmente progressivement.',
                    $level,
                    $moyenne
                );
            } else {
                $response = 'Je ne trouve pas encore ton suivi total. Demande a ton equipe de remplir les notes de consultation et de cours.';
            }
        } elseif (str_contains(mb_strtolower($message), 'defi')) {
            $response = 'Defi du jour: termine 2 jeux differents et explique ce que tu as appris en 2 phrases.';
        } elseif (str_contains(mb_strtolower($message), 'aide')) {
            $response = 'Astuce: commence par un jeu de memoire, puis enchaine avec un jeu social pour varier les competences.';
        }

        return $this->json([
            'success' => true,
            'reply' => $response,
        ]);
    }

    private function resolveLevelByAverage(float $moyenne): string
    {
        if ($moyenne < 10) {
            return 'FACILE';
        }

        if ($moyenne <= 15) {
            return 'MOYEN';
        }

        return 'DIFFICILE';
    }
}
