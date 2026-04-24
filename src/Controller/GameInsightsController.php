<?php

namespace App\Controller;

use App\AutiCareGameBundle\Service\GameIntelligenceFacade;
use App\Entity\ClinicalNote;
use App\Entity\GameSession;
use App\Form\ClinicalNoteType;
use App\Form\GameSessionType;
use App\Repository\ClinicalNoteRepository;
use App\Repository\GameSessionRepository;
use App\Repository\SuiviTotalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/jeu/insights')]
class GameInsightsController extends AbstractController
{
    #[Route('', name: 'app_game_insights_index', methods: ['GET'])]
    public function index(
        Request $request,
        SuiviTotalRepository $suiviTotalRepository,
        GameSessionRepository $gameSessionRepository,
        ClinicalNoteRepository $clinicalNoteRepository,
        GameIntelligenceFacade $gameIntelligenceFacade
    ): Response {
        $enfantId = (int) $request->query->get('enfantId', 0);
        $generateAi = (bool) $request->query->get('generateAi', false);

        if ($enfantId <= 0) {
            $lastSuivi = $suiviTotalRepository->createQueryBuilder('s')
                ->orderBy('s.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($lastSuivi !== null) {
                $enfantId = (int) $lastSuivi->getEnfantId();
            }
        }

        $insights = null;
        $recentSessions = [];
        $recentNotes = [];
        $aiSummary = null;
        $adaptivePlan = null;

        if ($enfantId > 0) {
            $recentSessions = $gameSessionRepository->findRecentByEnfant($enfantId, 12);
            $recentNotes = $clinicalNoteRepository->findRecentByEnfant($enfantId, 8);

            $payload = $gameIntelligenceFacade->buildInsightPayload($enfantId, $recentSessions, $recentNotes, $generateAi);
            $insights = $payload['insights'];
            $aiSummary = $payload['ai_summary'];
            $adaptivePlan = $payload['adaptive_plan'];

            if ($generateAi && is_array($aiSummary) && !empty($aiSummary['error'])) {
                $this->addFlash('danger', 'Generation IA indisponible: fallback local applique.');
            }
        }

        $gameSession = new GameSession();
        if ($enfantId > 0) {
            $gameSession->setEnfantId($enfantId);
        }

        $clinicalNote = new ClinicalNote();
        if ($enfantId > 0) {
            $clinicalNote->setEnfantId($enfantId);
        }

        $gameSessionForm = $this->createForm(GameSessionType::class, $gameSession, [
            'action' => $this->generateUrl('app_game_insights_new_session', ['enfantId' => $enfantId]),
            'method' => 'POST',
        ]);

        $clinicalNoteForm = $this->createForm(ClinicalNoteType::class, $clinicalNote, [
            'action' => $this->generateUrl('app_game_insights_new_note', ['enfantId' => $enfantId]),
            'method' => 'POST',
        ]);

        return $this->render('admin/pages/jeu_insights.html.twig', [
            'enfant_id' => $enfantId > 0 ? $enfantId : null,
            'insights' => $insights,
            'ai_summary' => $aiSummary,
            'adaptive_plan' => $adaptivePlan,
            'recent_sessions' => $recentSessions,
            'recent_notes' => $recentNotes,
            'game_session_form' => $gameSessionForm->createView(),
            'clinical_note_form' => $clinicalNoteForm->createView(),
        ]);
    }

    #[Route('/session/new', name: 'app_game_insights_new_session', methods: ['POST'])]
    public function newSession(
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $enfantId = (int) $request->query->get('enfantId', 0);

        $session = new GameSession();
        if ($enfantId > 0) {
            $session->setEnfantId($enfantId);
        }

        $form = $this->createForm(GameSessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'Session de jeu enregistree.');
            return $this->redirectToRoute('app_game_insights_index', ['enfantId' => $session->getEnfantId()]);
        }

        $this->addFlash('danger', 'Impossible d\'enregistrer la session. Verifie les champs.');

        return $this->redirectToRoute('app_game_insights_index', ['enfantId' => $enfantId > 0 ? $enfantId : null]);
    }

    #[Route('/note/new', name: 'app_game_insights_new_note', methods: ['POST'])]
    public function newNote(
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $enfantId = (int) $request->query->get('enfantId', 0);

        $note = new ClinicalNote();
        if ($enfantId > 0) {
            $note->setEnfantId($enfantId);
        }

        $form = $this->createForm(ClinicalNoteType::class, $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($note);
            $entityManager->flush();

            $this->addFlash('success', 'Note clinique enregistree.');
            return $this->redirectToRoute('app_game_insights_index', ['enfantId' => $note->getEnfantId()]);
        }

        $this->addFlash('danger', 'Impossible d\'enregistrer la note clinique. Verifie les champs.');

        return $this->redirectToRoute('app_game_insights_index', ['enfantId' => $enfantId > 0 ? $enfantId : null]);
    }
}
