<?php

namespace App\Controller;

use App\Entity\SuivieEntity;
use App\Repository\SuivieEntityRepository;
use App\Repository\TherapieEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/seance')]
class SuivieController extends AbstractController
{
    // ================= LIST =================
    #[Route('', name: 'admin_seance', methods: ['GET'])]
    public function index(SuivieEntityRepository $repo, TherapieEntityRepository $therapieRepo): Response
    {
        $sessions  = $repo->findAll();
        $therapies = $therapieRepo->findAll();

        $total = count($sessions);
        $planifie = 0; $enCours = 0; $termine = 0; $annule = 0;
        $sumH = 0; $sumS = 0; $sumA = 0;

        foreach ($sessions as $s) {
            $statut = $s->getStatut();
            if ($statut === 'normal') $planifie++;          // Normal = actif
            elseif ($statut === 'immobile') $enCours++;     // Immobile = en observation
            elseif ($statut === 'ne_bouge_pas') $enCours++; // Ne bouge pas = en observation
            elseif ($statut === 'n_entend_pas') $annule++;  // N'entend pas
            elseif ($statut === 'ne_voit_pas') $annule++;   // Ne voit pas
            $sumH += (int) $s->getScoreHumeur();
            $sumS += (int) $s->getScoreStress();
            $sumA += (int) $s->getScoreAttention();
        }

        return $this->render('admin/pages/seance.html.twig', [
            'sessions'     => $sessions,
            'therapies'    => $therapies,
            'total'        => $total,
            'planifie'     => $planifie,
            'enCours'      => $enCours,
            'termine'      => $termine,
            'annule'       => $annule,
            'avgHumeur'    => $total ? round($sumH / $total, 1) : 0,
            'avgStress'    => $total ? round($sumS / $total, 1) : 0,
            'avgAttention' => $total ? round($sumA / $total, 1) : 0,
        ]);
    }

    // ================= RECOMMENDATIONS (avant /{id} pour éviter conflit) =================
    #[Route('/recommendations', name: 'seance_recommendations', methods: ['POST'])]
    public function recommendations(Request $request, TherapieEntityRepository $therapieRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $humeur    = (int)($data['humeur']    ?? 0);
        $stress    = (int)($data['stress']    ?? 0);
        $attention = (int)($data['attention'] ?? 0);

        $niveauHumeur    = $this->getNiveau($humeur);
        $niveauStress    = $this->getNiveau($stress);
        $niveauAttention = $this->getNiveau($attention);

        $therapies = $therapieRepo->findByNiveaux($niveauHumeur, $niveauStress, $niveauAttention);

        $liste = [];
        foreach ($therapies as $t) {
            $liste[] = [
                'id'  => $t->getId(),
                'nom' => $t->getNomExercice() . ' (' . $t->getTypeExercice() . ')',
            ];
        }

        return $this->json($liste);
    }

    // ================= CREATE =================
    #[Route('/new', name: 'seance_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, TherapieEntityRepository $tr): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json(['success' => false, 'message' => 'Données invalides'], 400);
            }
            $s = new SuivieEntity();
            $this->hydrateFromForm($s, $data, $tr);
            $em->persist($s);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Séance ajoutée avec succès', 'id' => $s->getId()]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ================= SHOW =================
    #[Route('/{id}', name: 'seance_show', methods: ['GET'])]
    public function show(SuivieEntity $s): JsonResponse
    {
        return $this->json([
            'id'       => $s->getId(),
            'patient'  => $s->getNomEnfant(),
            'therapist'=> $s->getNomPsy(),
            'date'     => $s->getDateSuivie()?->format('Y-m-d'),
            'time'     => $s->getDateSuivie()?->format('H:i'),
            'status'   => $s->getStatut(),
            'notes'    => $s->getObservation(),
        ]);
    }

    // ================= UPDATE =================
    #[Route('/{id}/edit', name: 'seance_edit', methods: ['PUT', 'POST'])]
    public function edit(int $id, Request $request, SuivieEntityRepository $repo, EntityManagerInterface $em, TherapieEntityRepository $tr): JsonResponse
    {
        $s = $repo->find($id);
        if (!$s) {
            return $this->json(['success' => false, 'message' => 'Séance non trouvée'], 404);
        }
        try {
            $data = json_decode($request->getContent(), true);
            $this->hydrateFromForm($s, $data, $tr);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Séance modifiée avec succès']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ================= DELETE =================
    #[Route('/{id}/delete', name: 'seance_delete', methods: ['DELETE', 'POST'])]
    public function delete(int $id, SuivieEntityRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $s = $repo->find($id);
        if (!$s) {
            return $this->json(['success' => false, 'message' => 'Séance non trouvée'], 404);
        }
        $em->remove($s);
        $em->flush();
        return $this->json(['success' => true, 'message' => 'Séance supprimée']);
    }

    // ── HYDRATE ──────────────────────────────────────────────────────────
    private function hydrateFromForm(SuivieEntity $s, array $d, TherapieEntityRepository $repo): void
    {
        $s->setNomEnfant(trim($d['patient']   ?? $d['nomEnfant']  ?? ''));
        $s->setNomPsy(trim($d['therapist']    ?? $d['nomPsy']     ?? ''));
        $s->setStatut($d['status']            ?? $d['statut']     ?? 'planifie');
        $s->setObservation(mb_substr((string)($d['observation'] ?? ''), 0, 255));
        $s->setAge((int)($d['age'] ?? 0));
        $s->setEmailParent(!empty($d['emailParent']) ? $d['emailParent'] : null);
        $s->setScoreHumeur((int)($d['scoreHumeur']   ?? 0));
        $s->setScoreStress((int)($d['scoreStress']   ?? 0));
        $s->setScoreAttention((int)($d['scoreAttention'] ?? 0));
        $s->setNiveauSeance(isset($d['niveauSeance']) && $d['niveauSeance'] !== '' && $d['niveauSeance'] !== null
            ? (int)$d['niveauSeance'] : null);
        $s->setComportement((string)($d['comportement'] ?? ''));
        $s->setInteractionSociale((string)($d['interactionSociale'] ?? ''));

        // Date + heure
        $date = $d['date'] ?? '';
        $time = $d['time'] ?? '00:00';
        if ($date) {
            try { $s->setDateSuivie(new \DateTime($date . 'T' . $time)); }
            catch (\Exception) { $s->setDateSuivie(new \DateTime()); }
        }

        // Thérapie
        $tid = $d['therapieId'] ?? null;
        $s->setTherapie($tid ? $repo->find((int)$tid) : null);
    }

    // ── NIVEAU SCORE ─────────────────────────────────────────────────────
    private function getNiveau(int $score): string
    {
        if ($score <= 3) return 'faible';
        if ($score <= 6) return 'moyenne';
        return 'elevee';
    }
}
