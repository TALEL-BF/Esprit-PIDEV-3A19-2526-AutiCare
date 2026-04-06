<?php

namespace App\Controller;

<<<<<<< HEAD
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TherapieController extends AbstractController
{
    #[Route('/therapie', name: 'app_therapie')]
    public function index(): Response
    {
        // Données des types de thérapie
        $sessionTypes = [
            [
                'id' => 1,
                'title' => 'Thérapie ABA',
                'description' => 'Analyse du Comportement Appliqué — développement des compétences et réduction des comportements difficiles.',
                'icon' => 'fa-brain',
                'color' => 'primary',
                'badges' => ['En ligne', 'Présentiel']
            ],
            [
                'id' => 2,
                'title' => 'Orthophonie',
                'description' => 'Rééducation du langage oral, écrit et de la communication — pour tous les âges et niveaux.',
                'icon' => 'fa-comment-dots',
                'color' => 'secondary',
                'badges' => ['En ligne', 'Présentiel']
            ],
            [
                'id' => 3,
                'title' => 'Ergothérapie',
                'description' => 'Développement de l\'autonomie dans les activités quotidiennes et adaptation de l\'environnement.',
                'icon' => 'fa-hands',
                'color' => 'success',
                'badges' => ['En ligne', 'Présentiel']
            ],
            [
                'id' => 4,
                'title' => 'Musicothérapie',
                'description' => 'Utilisation de la musique pour favoriser la communication, l\'expression émotionnelle et la relaxation.',
                'icon' => 'fa-music',
                'color' => 'warning',
                'badges' => ['En ligne']
            ],
            [
                'id' => 5,
                'title' => 'Art-thérapie',
                'description' => 'Expression créative par les arts plastiques pour explorer les émotions et développer la confiance en soi.',
                'icon' => 'fa-palette',
                'color' => 'info',
                'badges' => ['En ligne', 'Présentiel']
            ],
            [
                'id' => 6,
                'title' => 'Zoothérapie',
                'description' => 'Médiation animale pour améliorer les interactions sociales et l\'équilibre émotionnel.',
                'icon' => 'fa-dog',
                'color' => 'danger',
                'badges' => ['Présentiel']
            ]
        ];

        // Données des thérapeutes
        $therapists = [
            [
                'id' => 1,
                'name' => 'Dr. Amira Ben Ali',
                'title' => 'Spécialiste ABA',
                'description' => '10 ans d\'expérience en thérapie comportementale pour TSA.',
                'emoji' => '👩‍⚕️',
                'color' => 'primary',
                'badges' => ['ABA', 'PECS']
            ],
            [
                'id' => 2,
                'name' => 'M. Karim Hamdi',
                'title' => 'Ergothérapeute',
                'description' => 'Spécialisé en intégration sensorielle et autonomie quotidienne.',
                'emoji' => '👨‍⚕️',
                'color' => 'secondary',
                'badges' => ['Ergothérapie', 'Sensoriel']
            ],
            [
                'id' => 3,
                'name' => 'Mme. Sonia Trabelsi',
                'title' => 'Orthophoniste',
                'description' => 'Experte en communication augmentée et langage TSA depuis 8 ans.',
                'emoji' => '👩‍🏫',
                'color' => 'success',
                'badges' => ['Orthophonie', 'CAA']
            ],
            [
                'id' => 4,
                'name' => 'Dr. Lina Mansouri',
                'title' => 'Art-thérapeute',
                'description' => 'Psychologue spécialisée en art-thérapie et gestion des émotions TSA.',
                'emoji' => '👩‍🎨',
                'color' => 'warning',
                'badges' => ['Art-thérapie', 'Psychologie']
            ]
        ];

        return $this->render('front/therapie/index.html.twig', [
            'sessionTypes' => $sessionTypes,
            'therapists' => $therapists
        ]);
    }
=======
use App\Entity\TherapieEntity;
use App\Repository\TherapieEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/therapie')]
final class TherapieController extends AbstractController
{
    #[Route('', name: 'admin_therapie')]
    public function index(TherapieEntityRepository $repo): Response
    {
        $therapies = $repo->findAll();

        // Stats niveaux (Humeur / Attention / Stress)
        $niveauKeys     = ['faible', 'moyenne', 'elevee'];
        $humeurStats    = array_fill_keys($niveauKeys, 0);
        $attentionStats = array_fill_keys($niveauKeys, 0);
        $stresseStats   = array_fill_keys($niveauKeys, 0);

        foreach ($therapies as $t) {
            $h = strtolower(trim((string) $t->getNiveauxHumeur()));
            $a = strtolower(trim((string) $t->getNiveauxAttention()));
            $s = strtolower(trim((string) $t->getNiveauxStresse()));
            if (isset($humeurStats[$h]))    $humeurStats[$h]++;
            if (isset($attentionStats[$a])) $attentionStats[$a]++;
            if (isset($stresseStats[$s]))   $stresseStats[$s]++;
        }

        // Stats types d'exercice
        $typesCounts = [];
        foreach ($therapies as $t) {
            $type = $t->getTypeExercice();
            $typesCounts[$type] = ($typesCounts[$type] ?? 0) + 1;
        }
        arsort($typesCounts);

        // ── Combinaisons Humeur + Attention + Stress ─────────────────────
        $combinaisons = [];
        foreach ($therapies as $t) {
            $h = strtolower(trim((string) $t->getNiveauxHumeur()));
            $a = strtolower(trim((string) $t->getNiveauxAttention()));
            $s = strtolower(trim((string) $t->getNiveauxStresse()));

            // Ignore les exercices avec des niveaux manquants
            if (!$h || !$a || !$s) continue;

            $key = $h . '|' . $a . '|' . $s;
            if (!isset($combinaisons[$key])) {
                $combinaisons[$key] = [
                    'humeur'    => $h,
                    'attention' => $a,
                    'stress'    => $s,
                    'count'     => 0,
                    'exercices' => [],
                ];
            }
            $combinaisons[$key]['count']++;
            $combinaisons[$key]['exercices'][] = $t->getNomExercice();
        }

        // Trier par nombre décroissant
        usort($combinaisons, fn($a, $b) => $b['count'] <=> $a['count']);

        return $this->render('admin/pages/therapie.html.twig', [
            'therapies'      => $therapies,
            'totalTherapies' => count($therapies),
            'typesCount'     => count($typesCounts),
            'chartLabels'    => array_keys($typesCounts),
            'chartValues'    => array_values($typesCounts),
            'humeurStats'    => $humeurStats,
            'attentionStats' => $attentionStats,
            'stresseStats'   => $stresseStats,
            'combinaisons'   => $combinaisons,
        ]);
    }

    #[Route('/new', name: 'app_therapie_new', methods: ['POST'])]
public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
{
    try {
        $data = json_decode($request->getContent(), true);

        $therapie = new TherapieEntity();
        $this->hydrateFromData($therapie, $data);

        // 🔥 VALIDATION
        $errors = $validator->validate($therapie);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => implode(", ", $messages)
            ], 400);
        }

        $em->persist($therapie);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Thérapie créée avec succès'
        ]);
    } catch (\Exception $e) {
        return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

    #[Route('/{id}', name: 'app_therapie_show', methods: ['GET'])]
    public function show(TherapieEntity $therapie): JsonResponse
    {
        return $this->json($this->serialize($therapie));
    }

    #[Route('/{id}/edit', name: 'app_therapie_edit', methods: ['POST'])]
    public function edit(Request $request, TherapieEntity $therapie, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $this->hydrateFromData($therapie, $data);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Thérapie modifiée avec succès',
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/delete', name: 'app_therapie_delete', methods: ['POST'])]
    public function delete(Request $request, TherapieEntity $therapie, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('delete' . $therapie->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }

        $em->remove($therapie);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Thérapie supprimée']);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function hydrateFromData(TherapieEntity $therapie, array $data): void
    {
        if (isset($data['nomExercice']))    $therapie->setNomExercice($data['nomExercice']);
        if (isset($data['typeExercice']))   $therapie->setTypeExercice($data['typeExercice']);
        if (isset($data['objectif']))       $therapie->setObjectif($data['objectif']);
        if (isset($data['description']))    $therapie->setDescription($data['description']);
        if (isset($data['dureeMin']))       $therapie->setDureeMin((int) $data['dureeMin']);
        if (isset($data['materiel']))       $therapie->setMateriel($data['materiel']);
        if (isset($data['adaptationTsa'])) $therapie->setAdaptationTsa($data['adaptationTsa']);
        if (isset($data['cible']))          $therapie->setCible($data['cible']);
        if (isset($data['niveauxHumeur']))  $therapie->setNiveauxHumeur($data['niveauxHumeur']);
        if (isset($data['niveauxAttention'])) $therapie->setNiveauxAttention($data['niveauxAttention']);
        if (isset($data['niveauxStresse'])) $therapie->setNiveauxStresse($data['niveauxStresse']);
        if (isset($data['comportement']))   $therapie->setComportement($data['comportement']);
        if (isset($data['interaction']))    $therapie->setInteraction($data['interaction']);
        if (isset($data['niveau']))         $therapie->setNiveau((int) $data['niveau']);
    }

    private function serialize(TherapieEntity $t): array
    {
        return [
            'id'              => $t->getId(),
            'nomExercice'     => $t->getNomExercice(),
            'typeExercice'    => $t->getTypeExercice(),
            'objectif'        => $t->getObjectif(),
            'description'     => $t->getDescription(),
            'dureeMin'        => $t->getDureeMin(),
            'materiel'        => $t->getMateriel(),
            'adaptationTsa'   => $t->getAdaptationTsa(),
            'cible'           => $t->getCible(),
            'niveauxHumeur'   => $t->getNiveauxHumeur(),
            'niveauxAttention' => $t->getNiveauxAttention(),
            'niveauxStresse'  => $t->getNiveauxStresse(),
            'comportement'    => $t->getComportement(),
            'interaction'     => $t->getInteraction(),
            'niveau'          => $t->getNiveau(),
        ];
    }
>>>>>>> Consultation
}