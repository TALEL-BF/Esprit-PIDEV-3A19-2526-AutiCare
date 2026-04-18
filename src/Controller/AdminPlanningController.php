<?php

namespace App\Controller;

use App\Entity\EmploiDuTemps;
use App\Form\EmploiDuTempsType;
use App\Repository\EmploiDuTempsRepository;
use App\Repository\RdvRepository;
use App\Repository\SeanceRepository;
use App\Service\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminPlanningController extends AbstractController
{
    #[Route('/admin/planning', name: 'admin_planning')]
    public function planning(
        Request $request,
        EntityManagerInterface $entityManager,
        EmploiDuTempsRepository $emploiDuTempsRepository,
        RdvRepository $rdvRepository,
        SeanceRepository $seanceRepository
    ): Response {
        $emploi = new EmploiDuTemps();
        $currentSchoolYear = $this->getCurrentSchoolYear();
        $emploi->setAnneeScolaire($currentSchoolYear);
        $form = $this->createForm(EmploiDuTempsType::class, $emploi, array_merge(
            $this->getEmploiFormOptions($rdvRepository, $seanceRepository),
            ['is_create' => true]
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emploi->setAnneeScolaire($currentSchoolYear);
            $this->applyEmploiSelection($form, $emploi);

            if (!$this->isEmploiSelectionValid($emploi)) {
                $form->addError(new FormError('Vous devez specifier SOIT un RDV SOIT une Seance (pas les deux en meme temps).'));
            } else {
                $entityManager->persist($emploi);
                $entityManager->flush();
                $this->addFlash('success', 'Ligne d\'emploi du temps ajoutee avec succes.');

                return $this->redirectToRoute('admin_planning');
            }
        }

        $emplois = $emploiDuTempsRepository->findAllByNewest();

        return $this->render('admin/pages/planning.html.twig', [
            'form' => $form->createView(),
            'emplois' => $emplois,
            'isEdit' => false,
            'entity' => null,
            'currentSchoolYear' => $currentSchoolYear,
        ]);
    }

    #[Route('/admin/planning/{id}/edit', name: 'admin_planning_edit', requirements: ['id' => '\\d+'])]
    public function editPlanning(
        Request $request,
        EmploiDuTemps $emploi,
        EntityManagerInterface $entityManager,
        EmploiDuTempsRepository $emploiDuTempsRepository,
        RdvRepository $rdvRepository,
        SeanceRepository $seanceRepository
    ): Response {
        $currentSchoolYear = $this->getCurrentSchoolYear();
        $emploi->setAnneeScolaire($currentSchoolYear);
        $formOptions = $this->getEmploiFormOptions($rdvRepository, $seanceRepository);
        $formOptions['data'] = $emploi;

        $form = $this->createForm(EmploiDuTempsType::class, $emploi, $formOptions);
        $form->get('rdvSelection')->setData($emploi->getIdRdv());
        $form->get('seanceSelection')->setData($emploi->getIdSeance());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emploi->setAnneeScolaire($currentSchoolYear);
            $this->applyEmploiSelection($form, $emploi);

            if (!$this->isEmploiSelectionValid($emploi)) {
                $form->addError(new FormError('Vous devez specifier SOIT un RDV SOIT une Seance (pas les deux en meme temps).'));
            } else {
                $entityManager->flush();
                $this->addFlash('success', 'Ligne d\'emploi du temps modifiee avec succes.');

                return $this->redirectToRoute('admin_planning');
            }
        }

        $emplois = $emploiDuTempsRepository->findAllByNewest();

        return $this->render('admin/pages/planning.html.twig', [
            'form' => $form->createView(),
            'emplois' => $emplois,
            'isEdit' => true,
            'entity' => $emploi,
            'currentSchoolYear' => $currentSchoolYear,
        ]);
    }

    #[Route('/admin/planning/{id}/delete', name: 'admin_planning_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deletePlanning(Request $request, EmploiDuTemps $emploi, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete_emploi_'.$emploi->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($emploi);
            $entityManager->flush();
            $this->addFlash('success', 'Ligne d\'emploi du temps supprimee avec succes.');
        } else {
            $this->addFlash('warning', 'Suppression planning refusee: jeton de securite invalide.');
        }

        return $this->redirectToRoute('admin_planning');
    }

    #[Route('/admin/planning/export/{type}', name: 'admin_planning_export')]
    public function export(string $type, EmploiDuTempsRepository $emploiRepository, EntityManagerInterface $entityManager, ExportService $exportService, \Twig\Environment $twig): Response
    {
        $emplois = $emploiRepository->findAllByNewest();
        $headings = ['Année Scolaire', 'Jour', 'Tranche Horaire', 'Type', 'Détails'];
        
        $stats = [
            'Total' => count($emplois),
            'RDV' => 0,
            'Séance' => 0,
            'Autres' => 0
        ];

        $data = [];
        foreach ($emplois as $emploi) {
            $typeLabel = 'Autre';
            $details = 'N/A';
            
            if ($emploi->getIdRdv()) {
                $typeLabel = 'RDV';
                $details = $this->fetchRdvDetails($entityManager, $emploi->getIdRdv());
                $stats['RDV']++;
            } elseif ($emploi->getIdSeance()) {
                $typeLabel = 'Séance';
                $details = $this->fetchSeanceDetails($entityManager, $emploi->getIdSeance());
                $stats['Séance']++;
            } else {
                $stats['Autres']++;
            }

            $data[] = [
                $emploi->getAnneeScolaire(),
                $emploi->getJourSemaine(),
                $emploi->getTrancheHoraire(),
                $typeLabel,
                $details
            ];
        }

        $filename = 'planning_list_' . date('Y-m-d');

        if ($type === 'pdf') {
            $html = $twig->render('admin/exports/export_pdf.html.twig', [
                'title' => 'Rapport du Planning',
                'headings' => $headings,
                'data' => $data,
                'stats' => $stats
            ]);
            return $exportService->generatePdf($html, $filename);
        }

        return $exportService->generateExcel($headings, $data, $filename);
    }

    private function fetchRdvDetails(EntityManagerInterface $entityManager, int $id): string
    {
        $row = $entityManager->getConnection()->fetchAssociative('SELECT type_consultation, date_heure_rdv FROM rdv WHERE id_rdv = :id', ['id' => $id]);
        if (!$row) return 'RDV #' . $id;
        $date = isset($row['date_heure_rdv']) ? (new \DateTime($row['date_heure_rdv']))->format('d/m/Y H:i') : 'N/A';
        return $row['type_consultation'] . ' (' . $date . ')';
    }

    private function fetchSeanceDetails(EntityManagerInterface $entityManager, int $id): string
    {
        $row = $entityManager->getConnection()->fetchAssociative('SELECT titre_seance, date_seance FROM seance WHERE id_seance = :id', ['id' => $id]);
        if (!$row) return 'Séance #' . $id;
        $date = isset($row['date_seance']) ? (new \DateTime($row['date_seance']))->format('d/m/Y H:i') : 'N/A';
        return $row['titre_seance'] . ' (' . $date . ')';
    }

    private function getEmploiFormOptions(RdvRepository $rdvRepository, SeanceRepository $seanceRepository): array
    {
        $rdvs = $rdvRepository->findAllByNewest();
        $seances = $seanceRepository->findAllByNewest();

        $rdvChoices = [];
        foreach ($rdvs as $rdv) {
            $label = sprintf(
                'RDV - %s - %s',
                $rdv->getTypeConsultation(),
                $rdv->getDateHeureRdv()?->format('d/m/Y H:i') ?? 'n/a'
            );
            $rdvChoices[$label] = $rdv->getId();
        }

        $seanceChoices = [];
        foreach ($seances as $seance) {
            $label = sprintf(
                'Seance - %s - %s',
                $seance->getTitreSeance(),
                $seance->getDateSeance()?->format('d/m/Y H:i') ?? 'n/a'
            );
            $seanceChoices[$label] = $seance->getId();
        }

        return [
            'rdv_choices' => $rdvChoices,
            'seance_choices' => $seanceChoices,
        ];
    }

    private function applyEmploiSelection($form, EmploiDuTemps $emploi): void
    {
        $rdvId = $form->get('rdvSelection')->getData();
        $seanceId = $form->get('seanceSelection')->getData();

        $emploi->setIdRdv($rdvId ? (int) $rdvId : null);
        $emploi->setIdSeance($seanceId ? (int) $seanceId : null);
    }

    private function isEmploiSelectionValid(EmploiDuTemps $emploi): bool
    {
        $hasRdv = null !== $emploi->getIdRdv();
        $hasSeance = null !== $emploi->getIdSeance();

        return $hasRdv xor $hasSeance;
    }

    private function getCurrentSchoolYear(): string
    {
        $year = (int) date('Y');
        $month = (int) date('n');

        $startYear = $month >= 9 ? $year : $year - 1;

        return sprintf('%d-%d', $startYear, $startYear + 1);
    }
}
