<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Evaluation;
use App\Form\EvaluationType;
use App\Repository\CoursRepository;
use App\Repository\EvaluationRepository;
use App\Services\EvaluationIAService;
use App\Services\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/evaluation')]
class EvaluationController extends AbstractController
{
    #[Route('/', name: 'admin_evaluation')]
    public function index(
        EvaluationRepository $evaluationRepository,
        CoursRepository $coursRepository,
        Request $request
    ): Response {
        $filterCourseId = $request->query->get('cours_id');

        if ($filterCourseId && $filterCourseId !== 'all') {
            $evaluations = $evaluationRepository->findBy(['idCours' => $filterCourseId]);
        } else {
            $evaluations = $evaluationRepository->findAll();
        }

        $allCourses = $coursRepository->findAll();

        $totalQuestions = count($evaluations);
        $totalScore = 0;
        foreach ($evaluations as $eval) {
            $totalScore += $eval->getScore();
        }
        $averageScore = $totalQuestions > 0 ? round($totalScore / $totalQuestions, 1) : 0;

        $evaluation = new Evaluation();
        $form = $this->createForm(EvaluationType::class, $evaluation);

        return $this->render('admin/pages/evaluation.html.twig', [
            'evaluations'    => $evaluations,
            'allCourses'     => $allCourses,
            'filterCourseId' => $filterCourseId,
            'totalQuestions' => $totalQuestions,
            'averageScore'   => $averageScore,
            'form'           => $form->createView(),
        ]);
    }

    #[Route('/save', name: 'admin_evaluation_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $em,
        EvaluationRepository $evaluationRepository,
        CoursRepository $coursRepository
    ): Response {
        $idEval = $request->request->get('id_eval');

        if ($idEval && !empty($idEval)) {
            $evaluation = $evaluationRepository->find($idEval);
            if (!$evaluation) {
                $this->addFlash('error', 'Question non trouvée');
                return $this->redirectToRoute('admin_evaluation');
            }
        } else {
            $evaluation = new Evaluation();
        }

        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        $question    = $request->request->get('question');
        $choix1      = $request->request->get('choix1');
        $choix2      = $request->request->get('choix2');
        $choix3      = $request->request->get('choix3');
        $bonneReponse = $request->request->get('bonne_reponse');
        $score       = $request->request->get('score');
        $coursId     = $request->request->get('cours_id');

        $errors = [];

        if (empty($question) || strlen($question) < 10) {
            $errors[] = 'La question doit contenir au moins 10 caractères';
        }
        if (empty($choix1))      { $errors[] = 'Le choix 1 ne peut pas être vide'; }
        if (empty($choix2))      { $errors[] = 'Le choix 2 ne peut pas être vide'; }
        if (empty($choix3))      { $errors[] = 'Le choix 3 ne peut pas être vide'; }
        if (empty($bonneReponse)){ $errors[] = 'Veuillez sélectionner la bonne réponse'; }
        if (empty($score) || $score < 1 || $score > 100) {
            $errors[] = 'Le score doit être compris entre 1 et 100';
        }
        if (empty($coursId))     { $errors[] = 'Veuillez sélectionner un cours'; }

        if ($choix1 && $choix2 && $choix3) {
            if ($choix1 === $choix2 || $choix1 === $choix3 || $choix2 === $choix3) {
                $errors[] = 'Les trois choix doivent être différents';
            }
        }

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('admin_evaluation');
        }

        $evaluation->setQuestion($question);
        $evaluation->setChoix1($choix1);
        $evaluation->setChoix2($choix2);
        $evaluation->setChoix3($choix3);
        $evaluation->setBonneReponse($bonneReponse);
        $evaluation->setScore((int) $score);
        $evaluation->setIdCours((int) $coursId);

        $cours = $coursRepository->find($coursId);
        if ($cours) {
            $evaluation->setCours($cours);
        }

        $em->persist($evaluation);
        $em->flush();

        $this->addFlash('success', 'Question enregistrée avec succès');
        return $this->redirectToRoute('admin_evaluation');
    }

    #[Route('/{id}/edit', name: 'admin_evaluation_edit_json', methods: ['GET'])]
    public function editJson(Evaluation $evaluation): Response
    {
        return $this->json([
            'id_eval'      => $evaluation->getIdEval(),
            'cours_id'     => $evaluation->getIdCours(),
            'cours_titre'  => $evaluation->getCours() ? $evaluation->getCours()->getTitre() : '',
            'question'     => $evaluation->getQuestion(),
            'choix1'       => $evaluation->getChoix1(),
            'choix2'       => $evaluation->getChoix2(),
            'choix3'       => $evaluation->getChoix3(),
            'bonne_reponse'=> $evaluation->getBonneReponse(),
            'score'        => $evaluation->getScore(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_evaluation_delete', methods: ['POST', 'DELETE'])]
    public function delete(Evaluation $evaluation, EntityManagerInterface $em): Response
    {
        try {
            $em->remove($evaluation);
            $em->flush();
            $this->addFlash('success', 'Question supprimée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression');
        }
        return $this->redirectToRoute('admin_evaluation');
    }

    #[Route('/generate-ia', name: 'admin_evaluation_generate_ia', methods: ['POST'])]
    public function generateIA(
        Request $request,
        EvaluationIAService $iaService,
        CoursRepository $coursRepository
    ): Response {
        $coursId = $request->request->get('cours_id');
        $nbQuestions = (int) $request->request->get('nb_questions', 5);

        if (!$coursId) {
            return $this->json(['error' => 'Veuillez sélectionner un cours'], 400);
        }

        $cours = $coursRepository->find($coursId);
        if (!$cours) {
            return $this->json(['error' => 'Cours non trouvé'], 404);
        }

        $questions = $iaService->genererQuestions($cours->getTitre(), $nbQuestions);

        if (empty($questions)) {
            return $this->json(['error' => 'Impossible de générer des questions pour le moment'], 500);
        }

        return $this->json([
            'success' => true,
            'questions' => $questions,
            'cours_id' => $cours->getIdCours(),
            'cours_titre' => $cours->getTitre()
        ]);
    }

    #[Route('/save-selected', name: 'admin_evaluation_save_selected', methods: ['POST'])]
    public function saveSelected(
        Request $request,
        CoursRepository $coursRepository,
        EntityManagerInterface $em
    ): Response {
        $data = json_decode($request->getContent(), true);
        $selectedQuestions = $data['questions'] ?? [];
        $coursId = $data['cours_id'] ?? null;

        if (!$coursId || empty($selectedQuestions)) {
            return $this->json(['error' => 'Aucune question à sauvegarder'], 400);
        }

        $cours = $coursRepository->find($coursId);
        if (!$cours) {
            return $this->json(['error' => 'Cours non trouvé'], 404);
        }

        $savedCount = 0;
        foreach ($selectedQuestions as $qData) {
            $evaluation = new Evaluation();
            $evaluation->setQuestion($qData['question']);
            $evaluation->setChoix1($qData['choix1']);
            $evaluation->setChoix2($qData['choix2']);
            $evaluation->setChoix3($qData['choix3']);
            $evaluation->setBonneReponse($qData['bonne_reponse']);
            $evaluation->setScore($qData['score'] ?? 1);
            $evaluation->setIdCours($cours->getIdCours());
            $evaluation->setCours($cours);

            $em->persist($evaluation);
            $savedCount++;
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => $savedCount . ' question(s) sauvegardée(s) avec succès',
            'count' => $savedCount
        ]);
    }

    /**
     * ─── Feedback IA après quiz ───
     * Reçoit le résultat du quiz et retourne un bilan personnalisé généré par Groq.
     */
    #[Route('/quiz-feedback', name: 'evaluation_quiz_feedback', methods: ['POST'])]
    public function quizFeedback(
        Request $request,
        GroqService $groqService
    ): Response {
        $data = json_decode($request->getContent(), true);

        $coursTitre     = $data['cours_titre']     ?? 'ce cours';
        $pct            = (int)  ($data['pct']     ?? 0);
        $correct        = (int)  ($data['correct'] ?? 0);
        $wrong          = (int)  ($data['wrong']   ?? 0);
        $wrongQuestions = $data['wrong_questions'] ?? [];

        $feedback = $groqService->genererFeedbackQuiz(
            $coursTitre,
            $pct,
            $correct,
            $wrong,
            $wrongQuestions
        );

        return $this->json($feedback);
    }
}