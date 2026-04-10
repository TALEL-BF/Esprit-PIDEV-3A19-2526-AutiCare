<?php

namespace App\Controller;

use App\Entity\MultiplayerEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class MultiplayerController extends AbstractController
{
    #[Route('/multiplayer', name: 'app_multiplayer')]
    public function index(): Response
    {
       return $this->render('front/events/multiplayer_index.html.twig');
    }

    #[Route('/multiplayer/create', name: 'app_multiplayer_create', methods: ['POST'])]
    public function createGame(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Connecte-toi d\'abord'], 401);
        }

        // Générer un code unique de 6 caractères
        $gameCode = strtoupper(substr(str_replace('-', '', Uuid::v4()->toRfc4122()), 0, 6));

        $game = new MultiplayerEvent();
        $game->setGameCode($gameCode);
        $game->setPlayer1Id($user->getId());
        $game->setStatus('waiting');
        $game->setCreatedAt(new \DateTimeImmutable());

        $em->persist($game);
        $em->flush();

        return $this->json([
            'success' => true,
            'gameCode' => $gameCode,
            'gameId' => $game->getId()
        ]);
    }

    #[Route('/multiplayer/join', name: 'app_multiplayer_join', methods: ['POST'])]
    public function joinGame(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Connecte-toi d\'abord'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $gameCode = $data['gameCode'] ?? null;

        $game = $em->getRepository(MultiplayerEvent::class)->findOneBy([
            'gameCode' => $gameCode,
            'status' => 'waiting'
        ]);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable ou déjà commencée'], 404);
        }

        $game->setPlayer2Id($user->getId());
        $game->setStatus('playing');

        $em->flush();

        return $this->json([
            'success' => true,
            'gameId' => $game->getId()
        ]);
    }

    #[Route('/multiplayer/game/{gameId}', name: 'app_multiplayer_game')]
public function game(int $gameId, EntityManagerInterface $em): Response
{
    $game = $em->getRepository(MultiplayerEvent::class)->find($gameId);
    
    if (!$game) {
        throw $this->createNotFoundException('Partie non trouvée');
    }

    $user = $this->getUser();
    $playerNumber = ($user->getId() === $game->getPlayer1Id()) ? 1 : 2;
    
    // 🔥 RÉCUPÉRER L'ÉVÉNEMENT (à adapter selon ta logique)
    // Par exemple, prendre le premier événement ou un événement spécifique
    $event = $em->getRepository(Event::class)->findOneBy([]); // ou find($eventId)
    
    return $this->render('front/events/multiplayer_game.html.twig', [
        'game' => $game,
        'playerNumber' => $playerNumber,
        'event' => $event  // ← AJOUTER CETTE LIGNE
    ]);
}
}