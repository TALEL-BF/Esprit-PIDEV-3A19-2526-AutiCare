<?php

namespace App\Controller;

use App\Repository\SuiviTotalRepository;
use App\Service\GameCatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JeuxController extends AbstractController
{
    #[Route('/jeux', name: 'app_jeux')]
    public function index(
        Request $request,
        SuiviTotalRepository $suiviTotalRepository,
        GameCatalogService $catalog
    ): Response
    {
        $enfantId = (int) $request->query->get('enfantId', 0);
        $recommendedLevel = null;
        $moyenne = null;

        if ($enfantId > 0) {
            $suivi = $suiviTotalRepository->findLastByEnfantId($enfantId);
            if ($suivi !== null && $suivi->getMoyenne() !== null) {
                $moyenne = (float) $suivi->getMoyenne();
                $recommendedLevel = $this->resolveLevelByAverage($moyenne);
            }
        }

        $games = $recommendedLevel ? $catalog->gamesForLevel($recommendedLevel) : $catalog->allGames();

        $categories = ['Tous', 'Communication', 'Emotions', 'Motricite', 'Memoire', 'Logique', 'Social'];

        return $this->render('front/jeux/index.html.twig', [
            'games' => $games,
            'categories' => $categories,
            'enfantId' => $enfantId > 0 ? $enfantId : null,
            'moyenne' => $moyenne,
            'recommended_level' => $recommendedLevel,
        ]);
    }

    #[Route('/jeu/{id}', name: 'app_jeu_play')]
    public function play(int $id, GameCatalogService $catalog): Response
    {
        $game = $catalog->gameById($id);
        if (!$game) {
            throw $this->createNotFoundException('Jeu non trouvé');
        }

        return $this->render('front/jeux/play.html.twig', [
            'game' => $game
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