<?php

namespace App\Controller;

use App\Service\AiChatService;
use App\Service\ChatContextService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AiChatController extends AbstractController
{
    #[Route('/api/chat', name: 'api_chat_send', methods: ['POST'])]
    public function send(Request $request, AiChatService $aiChatService, ChatContextService $chatContextService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        // Récupérer le contexte (RDVs, Séances) via le service
        $context = $chatContextService->getContext();

        // Obtenir la réponse de l'IA
        $response = $aiChatService->getAiResponse($message, $context);

        return new JsonResponse([
            'response' => $response
        ]);
    }
}
