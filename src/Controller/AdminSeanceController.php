<?php

namespace App\Controller;

use App\Entity\Seance;
use App\Form\SeanceType;
use App\Repository\SeanceRepository;
use App\Service\ZoomService;
use App\Service\WindowsNotificationService;
use App\Service\WebNotificationService;
use App\Service\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminSeanceController extends AbstractController
{
    #[Route('/admin/seance', name: 'admin_seance')]
    public function seance(Request $request, EntityManagerInterface $entityManager, SeanceRepository $seanceRepository, WindowsNotificationService $windowsNotifier, WebNotificationService $webNotifier, ZoomService $zoomService): Response
    {
        $seance = new Seance();
        $form = $this->createForm(SeanceType::class, $seance, $this->buildSeanceFormOptions($entityManager, true));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $zoomErrorMessage = null;

            try {
                $zoomMeeting = $zoomService->createMeeting(
                    $seance->getDateSeance() ?? new \DateTimeImmutable('now', new \DateTimeZone('Africa/Tunis')),
                    (string) ($seance->getTitreSeance() ?? 'Seance AutiCare'),
                    (int) ($seance->getDuree() ?? 60)
                );
                $seance->setZoomJoinUrl($zoomMeeting['join_url'] ?? null);
                $seance->setZoomStartUrl($zoomMeeting['start_url'] ?? null);
            } catch (\Throwable $e) {
                $zoomErrorMessage = $e->getMessage();
                $seance->setZoomJoinUrl(null);
                $seance->setZoomStartUrl(null);
            }

            $entityManager->persist($seance);
            $entityManager->flush();
            
            // Notification système Windows (admin)
            $windowsNotifier->notifyAdminNewSeance($seance);
            
            // Notification web (admin)
            $webData = $webNotifier->prepareNotificationData('info', $seance, 'create');
            $webDataJSON = $webNotifier->toJSON($webData);
            
            $this->addFlash('success', 'Seance ajoutee avec succes.');
            if ($zoomErrorMessage !== null) {
                $this->addFlash('warning', 'Seance creee, mais sans lien Zoom: ' . $zoomErrorMessage);
            }
            $this->addFlash('notification_web', $webDataJSON);

            if ($this->isApiRequest($request)) {
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Seance creee avec succes.',
                    'zoom_status' => $zoomErrorMessage === null ? 'created' : 'failed',
                    'zoom_error' => $zoomErrorMessage,
                    'seance' => [
                        'id' => $seance->getId(),
                        'titre' => $seance->getTitreSeance(),
                        'date' => $seance->getDateSeance()?->setTimezone(new \DateTimeZone('Africa/Tunis'))->format('Y-m-d\\TH:i:sP'),
                        'duree' => $seance->getDuree(),
                        'zoom_join_url' => $seance->getZoomJoinUrl(),
                    ],
                ], Response::HTTP_CREATED);
            }

            return $this->redirectToRoute('admin_seance');
        }

        $seances = $seanceRepository->findAllByNewest();

        return $this->render('admin/pages/seance.html.twig', [
            'form' => $form->createView(),
            'seances' => $seances,
            'isEdit' => false,
            'entity' => null,
        ]);
    }

    private function isApiRequest(Request $request): bool
    {
        $acceptHeader = (string) $request->headers->get('Accept', '');

        return $request->isXmlHttpRequest()
            || $request->getRequestFormat() === 'json'
            || str_contains(strtolower($acceptHeader), 'application/json');
    }

    #[Route('/admin/seance/{id}/edit', name: 'admin_seance_edit', requirements: ['id' => '\\d+'])]
    public function editSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager, SeanceRepository $seanceRepository, WindowsNotificationService $windowsNotifier, WebNotificationService $webNotifier): Response
    {
        $form = $this->createForm(SeanceType::class, $seance, $this->buildSeanceFormOptions($entityManager));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            // Notification système Windows (admin)
            $windowsNotifier->notifyAdminSeanceUpdate($seance);
            
            // Notification web (admin)
            $webData = $webNotifier->prepareNotificationData('info', $seance, 'update');
            $webDataJSON = $webNotifier->toJSON($webData);
            
            $this->addFlash('success', 'Seance modifiee avec succes.');
            $this->addFlash('notification_web', $webDataJSON);

            return $this->redirectToRoute('admin_seance');
        }

        $seances = $seanceRepository->findAllByNewest();

        return $this->render('admin/pages/seance.html.twig', [
            'form' => $form->createView(),
            'seances' => $seances,
            'isEdit' => true,
            'entity' => $seance,
        ]);
    }

    #[Route('/admin/seance/{id}/delete', name: 'admin_seance_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deleteSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete_seance_'.$seance->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($seance);
            $entityManager->flush();
            $this->addFlash('success', 'Seance supprimee avec succes.');
        } else {
            $this->addFlash('warning', 'Suppression seance refusee: jeton de securite invalide.');
        }

        return $this->redirectToRoute('admin_seance');
    }

    #[Route('/admin/seance/export/{type}', name: 'admin_seance_export')]
    public function export(string $type, SeanceRepository $seanceRepository, EntityManagerInterface $entityManager, ExportService $exportService, \Twig\Environment $twig): Response
    {
        $seances = $seanceRepository->findAllByNewest();
        $headings = ['Titre', 'Date', 'Durée (min)', 'Patient', 'Professeur', 'Cours', 'Statut'];
        
        $stats = [
            'Total' => count($seances),
            'Confirmées' => 0,
            'Planifiées' => 0,
            'Annulées' => 0
        ];

        $data = [];
        foreach ($seances as $seance) {
            $patient = $this->fetchNameById($entityManager, $seance->getIdAutiste());
            $prof = $this->fetchNameById($entityManager, $seance->getIdProfesseur());
            $cours = $this->fetchCourseTitleById($entityManager, $seance->getIdCours());
            $statut = $seance->getStatutSeance();

            // Stats
            $s = strtolower($statut);
            if ($s === 'confirme' || $s === 'confirmé' || $s === 'confirmée') $stats['Confirmées']++;
            elseif ($s === 'planifiee' || $s === 'planifiée') $stats['Planifiées']++;
            elseif ($s === 'annule' || $s === 'annulé' || $s === 'annulée') $stats['Annulées']++;
            
            $data[] = [
                $seance->getTitreSeance(),
                $seance->getDateSeance()?->format('d/m/Y H:i') ?? 'N/A',
                $seance->getDuree(),
                $patient,
                $prof,
                $cours,
                $statut
            ];
        }

        $filename = 'seance_list_' . date('Y-m-d');

        if ($type === 'pdf') {
            $html = $twig->render('admin/exports/export_pdf.html.twig', [
                'title' => 'Rapport des Séances',
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

    private function fetchCourseTitleById(EntityManagerInterface $entityManager, ?int $id): string
    {
        if (!$id) return 'N/A';
        $row = $entityManager->getConnection()->fetchAssociative('SELECT titre FROM cours WHERE id_cours = :id', ['id' => $id]);
        return $row['titre'] ?? ('Cours #' . $id);
    }

    private function buildSeanceFormOptions(EntityManagerInterface $entityManager, bool $isCreate = false): array
    {
        return [
            'is_create' => $isCreate,
            'patient_choices' => $this->fetchUserChoicesByRoles($entityManager, ['enfant']),
            'professor_choices' => $this->fetchUserChoicesByRoles($entityManager, ['professeur']),
            'course_choices' => $this->fetchCourseChoices($entityManager),
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
            $role = trim((string) ($row['role'] ?? ''));
            $displayName = trim($prenom . ' ' . $nom);
            $displayName = $displayName !== '' ? $displayName : ('Utilisateur #' . $id);
            $label = $displayName;

            $choices[$label] = $id;
        }

        return $choices;
    }

    private function fetchCourseChoices(EntityManagerInterface $entityManager): array
    {
        $rows = $entityManager->getConnection()->fetchAllAssociative(
            'SELECT id_cours, titre
             FROM cours
             ORDER BY titre ASC'
        );

        $choices = [];

        foreach ($rows as $row) {
            $id = (string) ($row['id_cours'] ?? '');
            if ($id === '') {
                continue;
            }

            $title = trim((string) ($row['titre'] ?? ''));
            $label = $title !== '' ? $title : ('Cours #' . $id);
            $choices[$label] = $id;
        }

        return $choices;
    }
}
