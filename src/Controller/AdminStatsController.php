<?php

// src/Controller/AdminStatsController.php

namespace App\Controller;

use App\Repository\CoursRepository;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminStatsController extends AbstractController
{
    #[Route('/admin/cours/stats', name: 'admin_cours_stats', methods: ['GET'])]
    public function getStats(CoursRepository $coursRepository): JsonResponse
    {
        $cours = $coursRepository->findAll();
        
        $typeStats = [
            'Académique' => 0,
            'Social' => 0,
            'Motricité' => 0,
            'Langage' => 0
        ];
        
        $niveauStats = [
            'Débutant' => 0,
            'Intermédiaire' => 0,
            'Avancé' => 0
        ];
        
        $totalMots = 0;
        $totalImages = 0;
        $dureeTotale = 0;
        
        foreach ($cours as $c) {
            if (isset($typeStats[$c->getTypeCours()])) {
                $typeStats[$c->getTypeCours()]++;
            }
            
            if (isset($niveauStats[$c->getNiveau()])) {
                $niveauStats[$c->getNiveau()]++;
            }
            
            $dureeTotale += $c->getDuree();
            
            if ($c->getMots()) {
                $totalMots += count(array_filter(explode(';', $c->getMots())));
            }
            
            if ($c->getImagesMots()) {
                $imagesCount = 0;
                $items = explode(';', $c->getImagesMots());
                foreach ($items as $item) {
                    if (strpos($item, ':') !== false) {
                        $parts = explode(':', $item, 2);
                        if (isset($parts[1])) {
                            $imagesCount += count(explode(',', $parts[1]));
                        }
                    }
                }
                $totalImages += $imagesCount;
            }
        }
        
        $totalCours = count($cours);
        
        $typePercentages = [];
        foreach ($typeStats as $type => $count) {
            $typePercentages[$type] = $totalCours > 0 ? round(($count / $totalCours) * 100, 1) : 0;
        }
        
        $niveauPercentages = [];
        foreach ($niveauStats as $niveau => $count) {
            $niveauPercentages[$niveau] = $totalCours > 0 ? round(($count / $totalCours) * 100, 1) : 0;
        }
        
        $moyenneMotsParCours = $totalCours > 0 ? round($totalMots / $totalCours, 1) : 0;
        $moyenneImagesParCours = $totalCours > 0 ? round($totalImages / $totalCours, 1) : 0;
        $dureeMoyenne = $totalCours > 0 ? round($dureeTotale / $totalCours, 1) : 0;
        
        return $this->json([
            'typeStats' => $typeStats,
            'niveauStats' => $niveauStats,
            'typePercentages' => $typePercentages,
            'niveauPercentages' => $niveauPercentages,
            'totalCours' => $totalCours,
            'totalMots' => $totalMots,
            'totalImages' => $totalImages,
            'dureeTotale' => $dureeTotale,
            'moyenneMotsParCours' => $moyenneMotsParCours,
            'moyenneImagesParCours' => $moyenneImagesParCours,
            'dureeMoyenne' => $dureeMoyenne,
        ]);
    }

    #[Route('/admin/stats', name: 'admin_stats')]
    public function stats(
        CoursRepository $coursRepository,
        EvaluationRepository $evaluationRepository
    ): Response {
        $cours = $coursRepository->findAll();
        $evaluations = $evaluationRepository->findAll();
        
        // ========== STATISTIQUES DE BASE ==========
        
        // Total cours
        $totalCours = count($cours);
        
        // Total mots
        $totalMots = 0;
        foreach ($cours as $c) {
            if ($c->getMots()) {
                $totalMots += count(array_filter(explode(';', $c->getMots())));
            }
        }
        
        // Total images
        $totalImages = 0;
        foreach ($cours as $c) {
            if ($c->getImagesMots()) {
                $items = explode(';', $c->getImagesMots());
                foreach ($items as $item) {
                    if (strpos($item, ':') !== false) {
                        $parts = explode(':', $item, 2);
                        if (isset($parts[1])) {
                            $totalImages += count(explode(',', $parts[1]));
                        }
                    }
                }
            }
        }
        
        // Total questions (évaluations)
        $totalQuestions = count($evaluations);
        
        // Score moyen global
        $scores = [];
        foreach ($evaluations as $e) {
            // Récupérer le score selon la méthode disponible
            if (method_exists($e, 'getScore')) {
                $scores[] = $e->getScore();
            } elseif (method_exists($e, 'getNote')) {
                $scores[] = $e->getNote();
            } elseif (method_exists($e, 'getPourcentage')) {
                $scores[] = $e->getPourcentage();
            }
        }
        $scoreMoyenGlobal = !empty($scores) ? round(array_sum($scores) / count($scores), 1) : 0;
        
        // ========== STATISTIQUES PAR TYPE ET NIVEAU ==========
        
        $typeStats = ['Académique' => 0, 'Social' => 0, 'Motricité' => 0, 'Langage' => 0];
        $niveauStats = ['Débutant' => 0, 'Intermédiaire' => 0, 'Avancé' => 0];
        
        foreach ($cours as $c) {
            if (isset($typeStats[$c->getTypeCours()])) {
                $typeStats[$c->getTypeCours()]++;
            }
            if (isset($niveauStats[$c->getNiveau()])) {
                $niveauStats[$c->getNiveau()]++;
            }
        }
        
        // ========== TOP COURS (basé sur le nombre de mots et d'images) ==========
        
        $topCours = [];
        foreach ($cours as $c) {
            $nbMots = $c->getMots() ? count(array_filter(explode(';', $c->getMots()))) : 0;
            $nbImages = 0;
            if ($c->getImagesMots()) {
                $items = explode(';', $c->getImagesMots());
                foreach ($items as $item) {
                    if (strpos($item, ':') !== false) {
                        $parts = explode(':', $item, 2);
                        if (isset($parts[1])) {
                            $nbImages += count(explode(',', $parts[1]));
                        }
                    }
                }
            }
            
            // Compter les évaluations pour ce cours
            $nbEvaluations = 0;
            foreach ($evaluations as $e) {
                if (method_exists($e, 'getCours') && $e->getCours() && $e->getCours()->getIdCours() === $c->getIdCours()) {
                    $nbEvaluations++;
                }
            }
            
            $topCours[] = [
                'titre' => $c->getTitre(),
                'nbMots' => $nbMots,
                'nbImages' => $nbImages,
                'nbEvaluations' => $nbEvaluations,
                'duree' => $c->getDuree()
            ];
        }
        
        // Trier par nombre d'évaluations (cours les plus populaires)
        usort($topCours, fn($a, $b) => $b['nbEvaluations'] - $a['nbEvaluations']);
        $topCours = array_slice($topCours, 0, 5);
        
        // ========== STATISTIQUES DE PROGRESSION ==========
        
        // Taux de complétion (basé sur les cours qui ont des évaluations)
        $coursAvecEvaluations = 0;
        foreach ($cours as $c) {
            $aDesEvals = false;
            foreach ($evaluations as $e) {
                if (method_exists($e, 'getCours') && $e->getCours() && $e->getCours()->getIdCours() === $c->getIdCours()) {
                    $aDesEvals = true;
                    break;
                }
            }
            if ($aDesEvals) {
                $coursAvecEvaluations++;
            }
        }
        $tauxCompletionGlobal = $totalCours > 0 ? round(($coursAvecEvaluations / $totalCours) * 100) : 0;
        
        // Taux de réussite (score > 70%)
        $reussites = 0;
        foreach ($scores as $score) {
            if ($score >= 70) {
                $reussites++;
            }
        }
        $tauxReussiteQuiz = !empty($scores) ? round(($reussites / count($scores)) * 100) : 0;
        
        // Objectif pédagogique (moyenne des scores)
        $objectifPedagogique = $scoreMoyenGlobal;
        
        // ========== STATISTIQUES DES QUIZ ==========
        
        $totalQuizRealises = count($evaluations);
        $meilleurScore = !empty($scores) ? max($scores) : 0;
        $tentativesMoyennes = $totalQuizRealises > 0 && $totalCours > 0 ? round($totalQuizRealises / $totalCours, 1) : 0;
        
        // Distribution des scores
        $faible = 0;
        $moyen = 0;
        $excellent = 0;
        foreach ($scores as $score) {
            if ($score < 40) $faible++;
            elseif ($score < 70) $moyen++;
            else $excellent++;
        }
        $totalScores = count($scores);
        $pourcentageFaible = $totalScores > 0 ? round(($faible / $totalScores) * 100) : 0;
        $pourcentageMoyen = $totalScores > 0 ? round(($moyen / $totalScores) * 100) : 0;
        $pourcentageExcellent = $totalScores > 0 ? round(($excellent / $totalScores) * 100) : 0;
        
        // ========== TEMPS D'APPRENTISSAGE ESTIMÉ ==========
        
        $dureeTotaleCours = 0;
        foreach ($cours as $c) {
            $dureeTotaleCours += $c->getDuree();
        }
        $totalMinutesApprentissage = $totalQuizRealises * 15 + $dureeTotaleCours; // 15 min par quiz + durée des cours
        
        // ========== RECOMMANDATIONS INTELLIGENTES ==========
        
        $recommandations = [];
        
        if ($tauxCompletionGlobal < 50) {
            $recommandations[] = [
                'icone' => '⚠️',
                'message' => 'Peu de cours ont des évaluations. Ajoutez des quiz pour suivre la progression.',
                'action' => 'Ajouter des quiz'
            ];
        }
        
        if ($tauxReussiteQuiz < 60 && $totalQuizRealises > 0) {
            $recommandations[] = [
                'icone' => '📝',
                'message' => 'Les résultats aux quiz sont faibles. Les questions sont peut-être trop difficiles.',
                'action' => 'Revoir les quiz'
            ];
        }
        
        if ($totalImages < $totalMots) {
            $recommandations[] = [
                'icone' => '🖼️',
                'message' => 'Certains mots n\'ont pas d\'images associées. Ajoutez des supports visuels.',
                'action' => 'Compléter les images'
            ];
        }
        
        if ($totalCours > 0 && $totalQuizRealises == 0) {
            $recommandations[] = [
                'icone' => '📋',
                'message' => 'Aucun quiz n\'a encore été créé. Les évaluations sont essentielles pour mesurer l\'apprentissage.',
                'action' => 'Créer des quiz'
            ];
        }
        
        if (count($recommandations) == 0) {
            $recommandations[] = [
                'icone' => '🌟',
                'message' => 'Excellent travail ! La plateforme est bien structurée et les étudiants progressent.',
                'action' => 'Continuer ainsi'
            ];
        }
        
        // ========== STATISTIQUES SUPPLÉMENTAIRES ==========
        
        // Nombre de mots par cours (moyenne)
        $moyenneMotsParCours = $totalCours > 0 ? round($totalMots / $totalCours, 1) : 0;
        
        // Nombre d'images par cours (moyenne)
        $moyenneImagesParCours = $totalCours > 0 ? round($totalImages / $totalCours, 1) : 0;
        
        // Cours avec le plus de mots
        $coursMaxMots = [];
        foreach ($cours as $c) {
            $nbMots = $c->getMots() ? count(array_filter(explode(';', $c->getMots()))) : 0;
            $coursMaxMots[] = ['titre' => $c->getTitre(), 'nbMots' => $nbMots];
        }
        usort($coursMaxMots, fn($a, $b) => $b['nbMots'] - $a['nbMots']);
        $topMotCours = !empty($coursMaxMots) ? $coursMaxMots[0]['titre'] : 'Aucun';
        $topMotCount = !empty($coursMaxMots) ? $coursMaxMots[0]['nbMots'] : 0;
        
        // Cours avec le plus d'images
        $coursMaxImages = [];
        foreach ($cours as $c) {
            $nbImages = 0;
            if ($c->getImagesMots()) {
                $items = explode(';', $c->getImagesMots());
                foreach ($items as $item) {
                    if (strpos($item, ':') !== false) {
                        $parts = explode(':', $item, 2);
                        if (isset($parts[1])) {
                            $nbImages += count(explode(',', $parts[1]));
                        }
                    }
                }
            }
            $coursMaxImages[] = ['titre' => $c->getTitre(), 'nbImages' => $nbImages];
        }
        usort($coursMaxImages, fn($a, $b) => $b['nbImages'] - $a['nbImages']);
        $topImageCours = !empty($coursMaxImages) ? $coursMaxImages[0]['titre'] : 'Aucun';
        $topImageCount = !empty($coursMaxImages) ? $coursMaxImages[0]['nbImages'] : 0;
        
        return $this->render('admin/stats/index.html.twig', [
            // Statistiques de base
            'totalCours' => $totalCours,
            'totalMots' => $totalMots,
            'totalImages' => $totalImages,
            'totalQuestions' => $totalQuestions,
            'scoreMoyenGlobal' => $scoreMoyenGlobal,
            
            // Top cours
            'topCours' => $topCours,
            'topMotCours' => $topMotCours,
            'topMotCount' => $topMotCount,
            'topImageCours' => $topImageCours,
            'topImageCount' => $topImageCount,
            
            // Progression
            'tauxCompletionGlobal' => $tauxCompletionGlobal,
            'tauxReussiteQuiz' => $tauxReussiteQuiz,
            'objectifPedagogique' => $objectifPedagogique,
            
            // Quiz
            'totalQuizRealises' => $totalQuizRealises,
            'meilleurScore' => $meilleurScore,
            'tentativesMoyennes' => $tentativesMoyennes,
            
            // Distribution
            'pourcentageFaible' => $pourcentageFaible,
            'pourcentageMoyen' => $pourcentageMoyen,
            'pourcentageExcellent' => $pourcentageExcellent,
            
            // Temps
            'totalMinutesApprentissage' => $totalMinutesApprentissage,
            
            // Recommandations
            'recommandations' => $recommandations,
            
            // Pour les graphiques
            'typeStats' => $typeStats,
            'niveauStats' => $niveauStats,
            'cours_list' => $cours,
            'tauxCompletion' => $tauxCompletionGlobal,
            'moyenneMotsParCours' => $moyenneMotsParCours,
            'moyenneImagesParCours' => $moyenneImagesParCours,
        ]);
    }
}