<?php

namespace App\Controller;

use App\Entity\SuivieEntity;
use App\Repository\SuivieEntityRepository;
use App\Repository\TherapieEntityRepository;
use App\Services\MailerService;
use App\Services\SuiviePdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\GeminiService;


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
            if ($statut === 'normal') $planifie++;
            elseif ($statut === 'immobile') $enCours++;
            elseif ($statut === 'ne_bouge_pas') $enCours++;
            elseif ($statut === 'n_entend_pas') $annule++;
            elseif ($statut === 'ne_voit_pas') $annule++;
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

    // ================= RECOMMENDATIONS =================
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
            'id'          => $s->getId(),
            'patient'     => $s->getNomEnfant(),
            'therapist'   => $s->getNomPsy(),
            'date'        => $s->getDateSuivie()?->format('Y-m-d'),
            'time'        => $s->getDateSuivie()?->format('H:i'),
            'status'      => $s->getStatut(),
            'notes'       => $s->getObservation(),
            'emailParent' => $s->getEmailParent(),
            'age'         => $s->getAge(),
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

    // ================= SEND REPORT BY EMAIL =================
    #[Route('/{id}/send-report', name: 'seance_send_report', methods: ['POST'])]
    public function sendReport(
        int                    $id,
        SuivieEntityRepository $repo,
        MailerService          $mailerService
    ): JsonResponse {
        // 1. Trouver la séance de référence
        $seance = $repo->find($id);
        if (!$seance) {
            return $this->json(['success' => false, 'message' => 'Séance non trouvée'], 404);
        }

        // 2. Vérifier que l'email parent existe
        if (!$seance->getEmailParent()) {
            return $this->json([
                'success' => false,
                'message' => "Aucun email parent enregistré pour cet enfant. Veuillez d'abord ajouter l'email dans les coordonnées.",
            ], 422);
        }

        // 3. Récupérer la dernière séance de l'enfant (pour le PDF)
        $nomEnfant   = $seance->getNomEnfant();
        $allSessions = $repo->findBy(['nomEnfant' => $nomEnfant], ['dateSuivie' => 'DESC']);
        $lastSeance  = $allSessions[0] ?? $seance;

        // 4. Envoyer
        $result = $mailerService->sendReport($nomEnfant, $lastSeance);

        $statusCode = $result['success'] ? 200 : 500;
        return $this->json($result, $statusCode);
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

        $date = $d['date'] ?? '';
        $time = $d['time'] ?? '00:00';
        if ($date) {
            try { $s->setDateSuivie(new \DateTime($date . 'T' . $time)); }
            catch (\Exception) { $s->setDateSuivie(new \DateTime()); }
        }

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


// ================= COMPTE-RENDU IA D'UNE SÉANCE =================
    #[Route('/{id}/compte-rendu-ia', name: 'seance_compte_rendu_ia', methods: ['POST'])]
    public function compteRenduIa(
        int                    $id,
        SuivieEntityRepository $repo,
        GeminiService          $geminiService
    ): JsonResponse {
        $seance = $repo->find($id);
        if (!$seance) {
            return $this->json(['success' => false, 'message' => 'Séance introuvable'], 404);
        }
 
        $result = $geminiService->generateCompteRendu(
            nomEnfant:          $seance->getNomEnfant() ?? '',
            age:                $seance->getAge() ?? 0,
            dateSuivie:         $seance->getDateSuivie()?->format('d/m/Y à H:i') ?? '—',
            nomPsy:             $seance->getNomPsy() ?? '',
            scoreHumeur:        $seance->getScoreHumeur() ?? 0,
            scoreStress:        $seance->getScoreStress() ?? 0,
            scoreAttention:     $seance->getScoreAttention() ?? 0,
            comportement:       $seance->getComportement() ?? '',
            interactionSociale: $seance->getInteractionSociale() ?? '',
            statut:             $seance->getStatut() ?? '',
            observation:        $seance->getObservation() ?? '',
            nomTherapie:        $seance->getTherapie()?->getNomExercice() ?? 'Aucune',
            niveauSeance:       $seance->getNiveauSeance() ?? 1
        );
 
        if (isset($result['error'])) {
            return $this->json(['success' => false, 'message' => $result['error']], 500);
        }
 
        return $this->json(['success' => true, 'compteRendu' => $result]);
    }
 
    // ================= ENVOYER COMPTE-RENDU PAR EMAIL =================
    #[Route('/{id}/envoyer-compte-rendu', name: 'seance_envoyer_compte_rendu', methods: ['POST'])]
    public function envoyerCompteRendu(
        int                    $id,
        Request                $request,
        SuivieEntityRepository $repo,
        MailerService          $mailerService
    ): JsonResponse {
        $seance = $repo->find($id);
        if (!$seance) {
            return $this->json(['success' => false, 'message' => 'Séance introuvable'], 404);
        }
 
        if (!$seance->getEmailParent()) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun email parent enregistré pour cette séance.'
            ], 422);
        }
 
        $data       = json_decode($request->getContent(), true);
        $compteRendu = $data['compteRendu'] ?? null;
 
        if (!$compteRendu) {
            return $this->json(['success' => false, 'message' => 'Données du compte-rendu manquantes.'], 400);
        }
 
        // Construire le HTML de l'email avec le compte-rendu
        $nomEnfant   = $seance->getNomEnfant();
        $emailParent = $seance->getEmailParent();
        $dateSuivie  = $seance->getDateSuivie()?->format('d/m/Y à H:i') ?? '—';
        $nomPsy      = $seance->getNomPsy();
 
        $result = $mailerService->sendCompteRendu(
            $emailParent,
            $nomEnfant,
            $dateSuivie,
            $nomPsy,
            $compteRendu
        );
 
        return $this->json($result, $result['success'] ? 200 : 500);
    }




}