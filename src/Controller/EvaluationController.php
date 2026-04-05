<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Evaluation;
use App\Repository\CoursRepository;
use App\Repository\EvaluationRepository;
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
            $evaluations = $evaluationRepository->findByCoursId($filterCourseId);
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
        
        return $this->render('admin/pages/evaluation.html.twig', [
            'evaluations' => $evaluations,
            'allCourses' => $allCourses,
            'filterCourseId' => $filterCourseId,
            'totalQuestions' => $totalQuestions,
            'averageScore' => $averageScore,
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
        
        
        $coursId = $request->request->get('cours_id');
        $question = $request->request->get('question');
        $choix1 = $request->request->get('choix1');
        $choix2 = $request->request->get('choix2');
        $choix3 = $request->request->get('choix3');
        $bonneReponse = $request->request->get('bonne_reponse');
        $score = $request->request->get('score');
        
      
        if (empty($coursId) || empty($question) || empty($choix1) || empty($choix2) || empty($choix3) || empty($bonneReponse)) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires');
            return $this->redirectToRoute('admin_evaluation');
        }
        
        $cours = $coursRepository->find($coursId);
        if (!$cours) {
            $this->addFlash('error', 'Cours non trouvé');
            return $this->redirectToRoute('admin_evaluation');
        }
        
        $evaluation->setCours($cours);
        $evaluation->setIdCours($coursId);
        $evaluation->setQuestion($question);
        $evaluation->setChoix1($choix1);
        $evaluation->setChoix2($choix2);
        $evaluation->setChoix3($choix3);
        $evaluation->setBonneReponse($bonneReponse);
        $evaluation->setScore($score ? (int)$score : 1);
        
        $em->persist($evaluation);
        $em->flush();
        
        $this->addFlash('success', 'Question enregistrée avec succès');
        return $this->redirectToRoute('admin_evaluation');
    }
    
    #[Route('/{id}/edit', name: 'admin_evaluation_edit_json', methods: ['GET'])]
    public function editJson(Evaluation $evaluation): Response
    {
        return $this->json([
            'id_eval' => $evaluation->getIdEval(),
            'cours_id' => $evaluation->getIdCours(),
            'cours_titre' => $evaluation->getCours() ? $evaluation->getCours()->getTitre() : '',
            'question' => $evaluation->getQuestion(),
            'choix1' => $evaluation->getChoix1(),
            'choix2' => $evaluation->getChoix2(),
            'choix3' => $evaluation->getChoix3(),
            'bonne_reponse' => $evaluation->getBonneReponse(),
            'score' => $evaluation->getScore(),
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_evaluation_delete', methods: ['POST', 'DELETE'])]
    public function delete(Evaluation $evaluation, EntityManagerInterface $em): Response
    {
        try {
            $em->remove($evaluation);
            $em->flush();
            $this->addFlash('success', 'Question supprimée avec succès');
            return $this->redirectToRoute('admin_evaluation');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression');
            return $this->redirectToRoute('admin_evaluation');
        }
    }
    

}