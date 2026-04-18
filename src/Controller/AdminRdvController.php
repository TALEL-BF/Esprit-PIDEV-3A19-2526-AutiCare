<?php

namespace App\Controller;

use App\Entity\Rdv;
use App\Form\RdvType;
use App\Repository\RdvRepository;
use App\Service\ZoomService;
use App\Service\SmsService;
use App\Service\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminRdvController extends AbstractController
{
    #[Route('/admin/rdv', name: 'admin_rdv')]
    public function rdv(Request $request, EntityManagerInterface $entityManager, RdvRepository $rdvRepository, ZoomService $zoomService, SmsService $smsService): Response
    {
        $rdv = new Rdv();
        $form = $this->createForm(RdvType::class, $rdv, $this->buildRdvFormOptions($entityManager, true));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $zoomErrorMessage = null;

            try {
                $zoomMeeting = $zoomService->createMeeting(
                    $rdv->getDateHeureRdv() ?? new \DateTimeImmutable('now', new \DateTimeZone('Africa/Tunis')),
                    (string) ($rdv->getTypeConsultation() ?? 'Rendez-vous AutiCare'),
                    (int) ($rdv->getDureeRdvMinutes() ?? 60)
                );
                $rdv->setZoomJoinUrl($zoomMeeting['join_url'] ?? null);
                $rdv->setZoomStartUrl($zoomMeeting['start_url'] ?? null);
            } catch (\Throwable $e) {
                $zoomErrorMessage = $e->getMessage();
                $rdv->setZoomJoinUrl(null);
                $rdv->setZoomStartUrl(null);
            }

            $entityManager->persist($rdv);
            $entityManager->flush();
            $this->addFlash('success', 'RDV ajoute avec succes.');

            if ($rdv->getIdAutiste()) {
                $this->notifyDateChangeViaSms($rdv, $entityManager, $smsService, true);
            }
            if ($zoomErrorMessage !== null) {
                $this->addFlash('warning', 'RDV cree, mais sans lien Zoom: ' . $zoomErrorMessage);
            }

            return $this->redirectToRoute('admin_rdv');
        }

        $rdvs = $rdvRepository->findAllByNewest();

        return $this->render('admin/pages/rdv.html.twig', [
            'form' => $form->createView(),
            'rdvs' => $rdvs,
            'isEdit' => false,
            'entity' => null,
        ]);
    }

    #[Route('/admin/rdv/{id}/edit', name: 'admin_rdv_edit', requirements: ['id' => '\\d+'])]
    public function editRdv(Request $request, Rdv $rdv, EntityManagerInterface $entityManager, RdvRepository $rdvRepository, SmsService $smsService): Response
    {
        $oldDate = $rdv->getDateHeureRdv() ? clone $rdv->getDateHeureRdv() : null;
        $form = $this->createForm(RdvType::class, $rdv, $this->buildRdvFormOptions($entityManager));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newDate = $rdv->getDateHeureRdv();
            $dateChanged = $oldDate === null || ($newDate !== null && $oldDate->getTimestamp() !== $newDate->getTimestamp());

            $entityManager->flush();
            $this->addFlash('success', 'RDV modifie avec succes.');

            if ($dateChanged && $rdv->getIdAutiste()) {
                $this->notifyDateChangeViaSms($rdv, $entityManager, $smsService);
            }

            return $this->redirectToRoute('admin_rdv');
        }

        $rdvs = $rdvRepository->findAllByNewest();

        return $this->render('admin/pages/rdv.html.twig', [
            'form' => $form->createView(),
            'rdvs' => $rdvs,
            'isEdit' => true,
            'entity' => $rdv,
        ]);
    }

    #[Route('/admin/rdv/{id}/delete', name: 'admin_rdv_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deleteRdv(Request $request, Rdv $rdv, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete_rdv_' . $rdv->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($rdv);
            $entityManager->flush();
            $this->addFlash('success', 'RDV supprime avec succes.');
        } else {
            $this->addFlash('warning', 'Suppression RDV refusee: jeton de securite invalide.');
        }

        return $this->redirectToRoute('admin_rdv');
    }

    private function buildRdvFormOptions(EntityManagerInterface $entityManager, bool $isCreate = false): array
    {
        return [
            'is_create' => $isCreate,
            'psychologue_choices' => $this->fetchUserChoicesByRoles($entityManager, ['psychologue', 'admin']),
            'patient_choices' => $this->fetchUserChoicesByRoles($entityManager, ['enfant']),
        ];
    }

    /**
     * @param string[] $roles
     */
    private function fetchUserChoicesByRoles(EntityManagerInterface $entityManager, array $roles): array
    {
        $connection = $entityManager->getConnection();
        $rows = $connection->fetchAllAssociative(
            'SELECT id, nom, prenom, role
             FROM user
             WHERE role IN (:roles)
             ORDER BY nom ASC, prenom ASC',
            ['roles' => $roles],
            ['roles' => \Doctrine\DBAL\ArrayParameterType::STRING]
        );

        $choices = [];

        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $prenom = trim((string) ($row['prenom'] ?? ''));
            $nom = trim((string) ($row['nom'] ?? ''));
            $displayName = trim($prenom . ' ' . $nom);
            $displayName = $displayName !== '' ? $displayName : ('Utilisateur #' . $id);

            $choices[$displayName] = (int) $id;
        }

        return $choices;
    }

    #[Route('/admin/rdv/export/{type}', name: 'admin_rdv_export')]
    public function export(string $type, RdvRepository $rdvRepository, EntityManagerInterface $entityManager, ExportService $exportService, \Twig\Environment $twig): Response
    {
        $rdvs = $rdvRepository->findAllByNewest();
        $headings = ['Type', 'Date', 'Durée (min)', 'Patient', 'Psychologue', 'Statut'];
        
        $stats = [
            'Total' => count($rdvs),
            'Confirmés' => 0,
            'Planifiés' => 0,
            'Annulés' => 0
        ];

        $data = [];
        foreach ($rdvs as $rdv) {
            $patient = $this->fetchNameById($entityManager, $rdv->getIdAutiste());
            $psy = $this->fetchNameById($entityManager, $rdv->getIdPsychologue());
            $statut = $rdv->getStatutRdv();

            // Stats calculation
            $s = strtolower($statut);
            if ($s === 'confirme' || $s === 'confirmé') $stats['Confirmés']++;
            elseif ($s === 'planifiee' || $s === 'planifiée') $stats['Planifiés']++;
            elseif ($s === 'annule' || $s === 'annulé') $stats['Annulés']++;
            
            $data[] = [
                $rdv->getTypeConsultation(),
                $rdv->getDateHeureRdv()?->format('d/m/Y H:i') ?? 'N/A',
                $rdv->getDureeRdvMinutes(),
                $patient,
                $psy,
                $statut
            ];
        }

        $filename = 'rdv_list_' . date('Y-m-d');

        if ($type === 'pdf') {
            $html = $twig->render('admin/exports/export_pdf.html.twig', [
                'title' => 'Rapport des Rendez-vous',
                'headings' => $headings,
                'data' => $data,
                'stats' => $stats
            ]);
            return $exportService->generatePdf($html, $filename);
        }

        return $exportService->generateExcel($headings, $data, $filename);
    }

    private function fetchNameById(EntityManagerInterface $entityManager, ?int $id): string
    {
        if (!$id) return 'N/A';
        $row = $entityManager->getConnection()->fetchAssociative('SELECT nom, prenom FROM user WHERE id = :id', ['id' => $id]);
        return $row ? (trim($row['prenom'] . ' ' . $row['nom']) ?: 'Utilisateur #' . $id) : 'N/A';
    }

    private function notifyDateChangeViaSms(Rdv $rdv, EntityManagerInterface $entityManager, SmsService $smsService, bool $isNew = false): void
    {
        $idAutiste = $rdv->getIdAutiste();
        if (!$idAutiste) {
            return;
        }

        $connection = $entityManager->getConnection();

        // Récupération des infos de l'enfant
        $userRow = $connection->fetchAssociative(
            'SELECT phone, nom, prenom FROM user WHERE id = :id',
            ['id' => $idAutiste]
        );

        // Récupération des infos du psychologue
        $psyRow = $connection->fetchAssociative(
            'SELECT nom, prenom FROM user WHERE id = :id',
            ['id' => $rdv->getIdPsychologue()]
        );

        $phone = $userRow['phone'] ?? null;
        $childName = trim(($userRow['prenom'] ?? '') . ' ' . ($userRow['nom'] ?? ''));
        $psyName = trim(($psyRow['prenom'] ?? '') . ' ' . ($psyRow['nom'] ?? ''));

        if (!$phone) {
            return;
        }

        $newDateStr = $rdv->getDateHeureRdv() ? $rdv->getDateHeureRdv()->format('d/m/Y à H:i') : 'non définie';
        $typeLabel = match ($rdv->getTypeConsultation()) {
            'premiere_consultation' => '1ère Consult.',
            'suivi' => 'Suivi',
            'urgence' => 'Urgent',
            'familiale' => 'Consult. Famille',
            'bilan' => 'Bilan Psy',
            default => 'RDV',
        };

        $action = $isNew ? 'programmé' : 'déplacé';

        $message = sprintf(
            "AutiCare: RDV (%s) %s %s avec %s le %s. Lien dans votre espace.",
            $typeLabel,
            $childName ?: 'enfant',
            $action,
            $psyName ?: 'Psy',
            $newDateStr
        );

        $success = $smsService->sendSms((string) $phone, $message);

        if (!$success) {
            $this->addFlash('warning', 'Le SMS n\'a pas pu être envoyé. Vérifiez votre compte Infobip.');
        } else {
            $this->addFlash('info', 'Un SMS de notification a été envoyé au parent.');
        }
    }
}
