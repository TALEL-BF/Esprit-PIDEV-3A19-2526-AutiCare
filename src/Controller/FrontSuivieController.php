<?php

namespace App\Controller;

use App\Repository\ArticleEntityRepository;
use App\Repository\SuivieEntityRepository;
use App\Services\GeminiService;
use App\Services\SuiviePdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\ParentUpload;
use App\Repository\ParentUploadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FrontSuivieController extends AbstractController
{
    public function __construct(
        private SuiviePdfService $pdfService,
        private GeminiService    $geminiService
    ) {}

    // ── PAGE PRINCIPALE ─────────────────────────────────────────────────
    #[Route('/suivie', name: 'app_suivie')]
    public function index(SuivieEntityRepository $repo): Response
    {
        $allSessions = $repo->findBy([], ['dateSuivie' => 'DESC']);
        $enfants = [];
        foreach ($allSessions as $s) {
            $nom = $s->getNomEnfant();
            if ($nom && !in_array($nom, $enfants)) {
                $enfants[] = $nom;
            }
        }
        sort($enfants);

        return $this->render('front/frontsuivie/index.html.twig', [
            'enfants'     => $enfants,
            'allSessions' => $allSessions,
        ]);
    }

    // ── DONNÉES ENFANT (AJAX) ────────────────────────────────────────────
    #[Route('/suivie/enfant', name: 'app_suivie_enfant', methods: ['GET'])]
    public function enfantData(
        Request                 $request,
        SuivieEntityRepository  $repo,
        ArticleEntityRepository $articleRepo
    ): Response {
        $nom = $request->query->get('nom', '');
        if (!$nom) {
            return $this->json(['error' => 'Nom manquant'], 400);
        }

        $sessions = $repo->findBy(['nomEnfant' => $nom], ['dateSuivie' => 'ASC']);
        if (empty($sessions)) {
            return $this->json(['error' => 'Aucune séance trouvée'], 404);
        }

        $last = end($sessions);
        $nb   = count($sessions);
        $avgH = round(array_sum(array_map(fn($s) => $s->getScoreHumeur()   ?? 0, $sessions)) / $nb, 1);
        $avgS = round(array_sum(array_map(fn($s) => $s->getScoreStress()   ?? 0, $sessions)) / $nb, 1);
        $avgA = round(array_sum(array_map(fn($s) => $s->getScoreAttention() ?? 0, $sessions)) / $nb, 1);

        $chartData = [];
        foreach ($sessions as $s) {
            $chartData[] = [
                'date'      => $s->getDateSuivie()?->format('d/m/Y') ?? '—',
                'humeur'    => $s->getScoreHumeur()   ?? 0,
                'stress'    => $s->getScoreStress()   ?? 0,
                'attention' => $s->getScoreAttention() ?? 0,
                'statut'    => $s->getStatut() ?? '',
            ];
        }

        $articles = $articleRepo->findBy([], ['likesCount' => 'DESC'], 4);
        $articlesData = [];
        foreach ($articles as $a) {
            $articlesData[] = [
                'id'        => $a->getId(),
                'titre'     => $a->getTitre(),
                'categorie' => $a->getCategorie(),
                'auteur'    => $a->getAuteur(),
                'extrait'   => mb_substr($a->getContenu() ?? '', 0, 120) . '...',
                'likes'     => $a->getLikesCount() ?? 0,
            ];
        }

        $th = $last->getTherapie();

        return $this->json([
            'nom'          => $nom,
            'age'          => $last->getAge(),
            'psy'          => $last->getNomPsy(),
            'email'        => $last->getEmailParent(),
            'nbSeances'    => $nb,
            'avgHumeur'    => $avgH,
            'avgStress'    => $avgS,
            'avgAttention' => $avgA,
            'lastStatut'   => $last->getStatut(),
            'lastDate'     => $last->getDateSuivie()?->format('d/m/Y'),
            'chartData'    => $chartData,
            'articles'     => $articlesData,
            'therapie'     => $th ? [
                'nom'         => $th->getNomExercice(),
                'type'        => $th->getTypeExercice(),
                'description' => $th->getDescription(),
                'duree'       => $th->getDureeMin(),
                'materiel'    => $th->getMateriel(),
                'objectif'    => $th->getObjectif(),
                'cible'       => $th->getCible(),
                'niveau'      => $th->getNiveau(),
                'adaptation'  => $th->getAdaptationTsa(),
            ] : null,
        ]);
    }

    // ── CONSEILS IA GEMINI (AJAX) ────────────────────────────────────────
    #[Route('/suivie/conseils-ia', name: 'app_suivie_conseils_ia', methods: ['POST'])]
    public function conseilsIa(Request $request, SuivieEntityRepository $repo): Response
    {
        $data = json_decode($request->getContent(), true);
        $nom  = trim($data['nom'] ?? '');

        if (!$nom) {
            return $this->json(['error' => 'Nom manquant'], 400);
        }

        $sessions = $repo->findBy(['nomEnfant' => $nom], ['dateSuivie' => 'ASC']);
        if (empty($sessions)) {
            return $this->json(['error' => 'Aucune séance trouvée'], 404);
        }

        $last = end($sessions);
        $nb   = count($sessions);
        $avgH = round(array_sum(array_map(fn($s) => $s->getScoreHumeur()   ?? 0, $sessions)) / $nb, 1);
        $avgS = round(array_sum(array_map(fn($s) => $s->getScoreStress()   ?? 0, $sessions)) / $nb, 1);
        $avgA = round(array_sum(array_map(fn($s) => $s->getScoreAttention() ?? 0, $sessions)) / $nb, 1);

        $nomTherapie = $last->getTherapie()?->getNomExercice() ?? '';
        $age         = $last->getAge() ?? 0;
        $statut      = $last->getStatut() ?? 'normal';

        $result = $this->geminiService->generateAdvice(
            $nom, $age, $avgH, $avgS, $avgA, $statut, $nomTherapie, $nb
        );

        return $this->json($result);
    }

    // ── TÉLÉCHARGER PDF avec conseils IA ────────────────────────────────
    #[Route('/suivie/pdf/{nom}', name: 'app_suivie_pdf')]
    public function downloadPdf(
        string                 $nom,
        Request                $request,
        SuivieEntityRepository $repo
    ): Response {
        $sessions = $repo->findBy(['nomEnfant' => $nom], ['dateSuivie' => 'ASC']);
        if (empty($sessions)) {
            throw $this->createNotFoundException('Enfant non trouvé');
        }

        $last = end($sessions);
        $nb   = count($sessions);
        $avgH = round(array_sum(array_map(fn($s) => $s->getScoreHumeur()   ?? 0, $sessions)) / $nb, 1);
        $avgS = round(array_sum(array_map(fn($s) => $s->getScoreStress()   ?? 0, $sessions)) / $nb, 1);
        $avgA = round(array_sum(array_map(fn($s) => $s->getScoreAttention() ?? 0, $sessions)) / $nb, 1);

        // Récupérer les conseils IA depuis la requête (passés en query param JSON encodé)
        // ou les générer à la volée si absent
        $aiDataRaw = $request->query->get('ai');
        $aiData    = null;

        if ($aiDataRaw) {
            $aiData = json_decode(base64_decode($aiDataRaw), true);
        }

        if (!$aiData || empty($aiData['conseils'])) {
            // Générer les conseils IA maintenant
            $nomTherapie = $last->getTherapie()?->getNomExercice() ?? '';
            $age         = $last->getAge() ?? 0;
            $statut      = $last->getStatut() ?? 'normal';
            $aiData = $this->geminiService->generateAdvice(
                $nom, $age, $avgH, $avgS, $avgA, $statut, $nomTherapie, $nb
            );
        }

        $pdfBytes = $this->pdfService->generate(
            $nom, $sessions, $last, $nb, $avgH, $avgS, $avgA, $aiData
        );

        return new Response($pdfBytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->pdfService->filename($nom) . '"',
        ]);
    }

#[Route('/suivie/upload-document', name: 'app_suivie_upload_document', methods: ['POST'])]
    public function uploadDocument(
        Request                $request,
        EntityManagerInterface $em,
        SuivieEntityRepository $repo
    ): Response {
        $nom         = trim($request->request->get('nom_enfant', ''));
        $emailParent = trim($request->request->get('email_parent', ''));
        $subject     = trim($request->request->get('subject', ''));
        $file        = $request->files->get('document');
 
        // Validation basique
        if (!$nom || !$emailParent || !$subject) {
            return $this->json(['success' => false, 'message' => 'Tous les champs sont obligatoires.'], 400);
        }
 
        if (!$file || !$file->isValid()) {
            return $this->json(['success' => false, 'message' => 'Fichier invalide ou manquant.'], 400);
        }
 
        // Vérifier l'extension
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $ext     = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $allowed)) {
            return $this->json(['success' => false, 'message' => 'Format non autorisé. Formats acceptés : PDF, JPG, PNG, DOC, DOCX.'], 400);
        }
 
        // Taille max 5 Mo
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['success' => false, 'message' => 'Fichier trop volumineux (max 5 Mo).'], 400);
        }
 
        // Dossier de destination
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/parent_docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
 
        // Nom unique
        $newFileName = uniqid('doc_') . '_' . time() . '.' . $ext;
 
        try {
            $file->move($uploadDir, $newFileName);
        } catch (FileException $e) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier.'], 500);
        }
 
        // Trouver la dernière séance de l'enfant pour l'ID
        $sessions  = $repo->findBy(['nomEnfant' => $nom], ['dateSuivie' => 'DESC']);
        $lastId    = !empty($sessions) ? $sessions[0]->getId() : null;
 
        // Sauvegarder en base
        $upload = new ParentUpload();
        $upload->setNomEnfant($nom);
        $upload->setEmailParent($emailParent);
        $upload->setSubject($subject);
        $upload->setFileName($file->getClientOriginalName());
        $upload->setFilePath('/uploads/parent_docs/' . $newFileName);
        $upload->setIdSuivie($lastId);
        $upload->setSeen(false);
 
        $em->persist($upload);
        $em->flush();
 
        return $this->json([
            'success' => true,
            'message' => 'Document envoyé avec succès ! Le psychologue sera notifié.'
        ]);
    }



}