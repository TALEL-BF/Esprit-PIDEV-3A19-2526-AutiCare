<?php

namespace App\Controller;

use App\Entity\SuiviTotal;
use App\Form\SuiviTotalType;
use App\Repository\SuiviTotalRepository;
use App\Service\NiveauResolverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/suivi/total')]
final class SuiviTotalController extends AbstractController
{
    #[Route('', name: 'app_suivi_total_index', methods: ['GET', 'POST'])]
    public function index(Request $request, SuiviTotalRepository $suiviTotalRepository, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search');
        $niveau = $request->query->get('niveau');
        $tri = $request->query->get('tri', 'id_desc');

        $suiviTotals = $suiviTotalRepository->searchAndFilter($search, $niveau, $tri);
        $statistics = $suiviTotalRepository->findStatistics($search, $niveau);

        // Formulaire pour ajouter un nouveau suivi
        $suiviTotal = new SuiviTotal();
        $form = $this->createForm(SuiviTotalType::class, $suiviTotal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($suiviTotal);
            $entityManager->flush();
            $this->addFlash('success', 'Le suivi a été ajouté avec succès.');
            return $this->redirectToRoute('app_suivi_total_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('suivi_total/index.html.twig', [
            'suivi_totals' => $suiviTotals,
            'statistics' => $statistics,
            'current_search' => $search,
            'current_niveau' => $niveau,
            'current_tri' => $tri,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/export/csv', name: 'app_suivi_total_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, SuiviTotalRepository $suiviTotalRepository): Response
    {
        $search = $request->query->get('search');
        $niveau = $request->query->get('niveau');
        $tri = $request->query->get('tri', 'id_desc');

        $rows = $suiviTotalRepository->findForCsv($search, $niveau, $tri);

        $content = "id,enfant_id,note_cours,note_consultation,moyenne_generale,remarque,niveau\n";

        foreach ($rows as $row) {
            $content .= implode(',', [
                $row->getId(),
                $row->getEnfantId(),
                number_format((float) $row->getNoteCours(), 2, '.', ''),
                number_format((float) $row->getNoteConsultation(), 2, '.', ''),
                number_format((float) $row->getMoyenneGenerale(), 2, '.', ''),
                '"' . str_replace('"', '""', (string) ($row->getRemarque() ?? '')) . '"',
                '"' . str_replace('"', '""', (string) ($row->getNiveauJeu()?->getLibelle() ?? '')) . '"',
            ]) . "\n";
        }

        return new Response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="suivi_total.csv"',
        ]);
    }

    #[Route('/export/json', name: 'app_suivi_total_export_json', methods: ['GET'])]
    public function exportJson(Request $request, SuiviTotalRepository $suiviTotalRepository): Response
    {
        $search = $request->query->get('search');
        $niveau = $request->query->get('niveau');
        $tri = $request->query->get('tri', 'id_desc');

        $rows = $suiviTotalRepository->findForCsv($search, $niveau, $tri);

        $data = array_map(static function (SuiviTotal $row) {
            return [
                'id' => $row->getId(),
                'enfant_id' => $row->getEnfantId(),
                'note_cours' => number_format((float) $row->getNoteCours(), 2, '.', ''),
                'note_consultation' => number_format((float) $row->getNoteConsultation(), 2, '.', ''),
                'moyenne_generale' => number_format((float) $row->getMoyenneGenerale(), 2, '.', ''),
                'remarque' => $row->getRemarque(),
                'niveau' => $row->getNiveauJeu()?->getLibelle(),
            ];
        }, $rows);

        return new Response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="suivi_total.json"',
        ]);
    }

    #[Route('/export/pdf', name: 'app_suivi_total_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, SuiviTotalRepository $suiviTotalRepository): Response
    {
        $search = $request->query->get('search');
        $niveau = $request->query->get('niveau');
        $tri = $request->query->get('tri', 'id_desc');

        $rows = $suiviTotalRepository->findForCsv($search, $niveau, $tri);
        $html = $this->renderView('suivi_total/pdf.html.twig', [
            'rows' => $rows,
            'statistics' => $suiviTotalRepository->findStatistics($search, $niveau),
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="suivi_total.pdf"',
        ]);
    }

    #[Route('/niveaux', name: 'app_suivi_total_niveaux', methods: ['GET'])]
    public function niveaux(SuiviTotalRepository $suiviTotalRepository): Response
    {
        $records = $suiviTotalRepository->findResumePourNiveaux();

        return $this->render('suivi_total/niveaux.html.twig', [
            'records' => $records,
        ]);
    }

    #[Route('/new', name: 'app_suivi_total_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        NiveauResolverService $niveauResolverService
    ): Response {
        $suiviTotal = new SuiviTotal();
        $form = $this->createForm(SuiviTotalType::class, $suiviTotal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $suiviTotal->calculerMoyenneGenerale();

            $niveau = $niveauResolverService->findByMoyenne((float) $suiviTotal->getMoyenneGenerale());

            if ($niveau === null) {
                $this->addFlash('danger', 'Aucun niveau ne correspond à cette moyenne générale.');

                return $this->render('suivi_total/new.html.twig', [
                    'suivi_total' => $suiviTotal,
                    'form' => $form->createView(),
                ]);
            }

            $suiviTotal->setNiveauJeu($niveau);

            $entityManager->persist($suiviTotal);
            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été ajouté avec succès.');

            return $this->redirectToRoute('app_suivi_total_index');
        }

        return $this->render('suivi_total/new.html.twig', [
            'suivi_total' => $suiviTotal,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_suivi_total_show', methods: ['GET'])]
    public function show(SuiviTotal $suiviTotal): Response
    {
        return $this->render('suivi_total/show.html.twig', [
            'suivi_total' => $suiviTotal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_suivi_total_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        SuiviTotal $suiviTotal,
        EntityManagerInterface $entityManager,
        NiveauResolverService $niveauResolverService
    ): Response {
        $form = $this->createForm(SuiviTotalType::class, $suiviTotal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $suiviTotal->calculerMoyenneGenerale();

           $niveau = $niveauResolverService->findByMoyenne(
    (float) $suiviTotal->getMoyenneGenerale()
);

            if ($niveau === null) {
                $this->addFlash('danger', 'Aucun niveau ne correspond à cette moyenne générale.');

                return $this->render('suivi_total/edit.html.twig', [
                    'suivi_total' => $suiviTotal,
                    'form' => $form->createView(),
                ]);
            }

            $suiviTotal->setNiveauJeu($niveau);
            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été modifié avec succès.');

            return $this->redirectToRoute('app_suivi_total_index');
        }

        return $this->render('suivi_total/edit.html.twig', [
            'suivi_total' => $suiviTotal,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_suivi_total_delete', methods: ['POST'])]
    public function delete(Request $request, SuiviTotal $suiviTotal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $suiviTotal->getId(), $request->request->get('_token'))) {
            $entityManager->remove($suiviTotal);
            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_suivi_total_index');
    }
}
