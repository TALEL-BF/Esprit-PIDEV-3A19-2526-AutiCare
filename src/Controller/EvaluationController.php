<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Evaluation;
use App\Form\EvaluationType;
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
        
        // Créer un nouveau formulaire vide
        $evaluation = new Evaluation();
        $form = $this->createForm(EvaluationType::class, $evaluation);
        
        return $this->render('admin/pages/evaluation.html.twig', [
            'evaluations' => $evaluations,
            'allCourses' => $allCourses,
            'filterCourseId' => $filterCourseId,
            'totalQuestions' => $totalQuestions,
            'averageScore' => $averageScore,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/save', name: 'admin_evaluation_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $em,
        EvaluationRepository $evaluationRepository
    ): Response {
        $idEval = $request->request->get('id_eval');
        
        if ($idEval && !empty($idEval)) {
            $evaluation = $evaluationRepository->find($idEval);
            if (!$evaluation) {
                $this->addFlash('error', 'Question non trouvée');
                return $this->redirectToRoute('admin_evaluation');
            }
            $form = $this->createForm(EvaluationType::class, $evaluation);
        } else {
            $evaluation = new Evaluation();
            $form = $this->createForm(EvaluationType::class, $evaluation);
        }
        
        $form->handleRequest($request);
        
        // Validation spécifique : vérifier que les choix sont différents
        if ($form->isSubmitted()) {
            $choix1 = $form->get('choix1')->getData();
            $choix2 = $form->get('choix2')->getData();
            $choix3 = $form->get('choix3')->getData();
            
            if ($choix1 === $choix2 || $choix1 === $choix3 || $choix2 === $choix3) {
                $this->addFlash('error', 'Les trois choix doivent être différents');
                return $this->redirectToRoute('admin_evaluation');
            }
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'ID du cours depuis l'entité Cours
            $cours = $evaluation->getCours();
            if ($cours) {
                $evaluation->setIdCours($cours->getIdCours());
            }
            
            $em->persist($evaluation);
            $em->flush();
            
            $this->addFlash('success', 'Question enregistrée avec succès');
            return $this->redirectToRoute('admin_evaluation');
        }
        
        // Si le formulaire n'est pas valide, afficher les erreurs
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
        
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