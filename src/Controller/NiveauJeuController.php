<?php

namespace App\Controller;

use App\Entity\NiveauJeu;
use App\Form\NiveauJeuType;
use App\Repository\NiveauJeuRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/niveau/jeu')]
final class NiveauJeuController extends AbstractController
{
    #[Route(name: 'app_niveau_jeu_index', methods: ['GET', 'POST'])]
    public function index(Request $request, NiveauJeuRepository $niveauJeuRepository, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search');
        $niveau = $request->query->get('niveau');
        $tri = $request->query->get('tri', 'id_desc');

        $niveauJeus = $niveauJeuRepository->searchAndFilter($search, $niveau, $tri);

        // Formulaire pour ajouter un nouveau niveau
        $niveauJeu = new NiveauJeu();
        $form = $this->createForm(NiveauJeuType::class, $niveauJeu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($niveauJeu);
            $entityManager->flush();
            $this->addFlash('success', 'Le niveau a été ajouté avec succès.');
            return $this->redirectToRoute('app_niveau_jeu_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('niveau_jeu/index.html.twig', [
            'niveau_jeus' => $niveauJeus,
            'current_search' => $search,
            'current_niveau' => $niveau,
            'current_tri' => $tri,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_niveau_jeu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $niveauJeu = new NiveauJeu();
        $form = $this->createForm(NiveauJeuType::class, $niveauJeu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($niveauJeu);
            $entityManager->flush();

            $this->addFlash('success', 'Le niveau a été ajouté avec succès.');

            return $this->redirectToRoute('app_niveau_jeu_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('niveau_jeu/new.html.twig', [
            'niveau_jeu' => $niveauJeu,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_niveau_jeu_show', methods: ['GET'])]
    public function show(NiveauJeu $niveauJeu): Response
    {
        return $this->render('niveau_jeu/show.html.twig', [
            'niveau_jeu' => $niveauJeu,
        ]);
    }

    #[Route('/export/csv', name: 'app_niveau_jeu_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, NiveauJeuRepository $niveauJeuRepository): Response
    {
        $search = $request->query->get('search');
        $niveau = $request->query->get('niveau');
        $tri = $request->query->get('tri', 'id_desc');

        $rows = $niveauJeuRepository->searchAndFilter($search, $niveau, $tri);

        $content = "id,libelle,min_moyenne,max_moyenne,description\n";
        foreach ($rows as $row) {
            $description = $row->getDescription() !== null ? str_replace('"', '""', $row->getDescription()) : '';
            $content .= sprintf(
                "%d,%s,%.2f,%.2f,\"%s\"\n",
                $row->getId(),
                $row->getLibelle(),
                $row->getMinMoyenne(),
                $row->getMaxMoyenne(),
                $description
            );
        }

        return new Response($content, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="niveau_jeux.csv"',
        ]);
    }

    #[Route('/export/json', name: 'app_niveau_jeu_export_json', methods: ['GET'])]
    public function exportJson(Request $request, NiveauJeuRepository $niveauJeuRepository): Response
    {
        $search = $request->query->get('search');
        $niveau = $request->query->get('niveau');
        $tri = $request->query->get('tri', 'id_desc');

        $rows = $niveauJeuRepository->searchAndFilter($search, $niveau, $tri);

        $data = array_map(static function ($row) {
            return [
                'id' => $row->getId(),
                'libelle' => $row->getLibelle(),
                'min_moyenne' => number_format((float) $row->getMinMoyenne(), 2, '.', ''),
                'max_moyenne' => number_format((float) $row->getMaxMoyenne(), 2, '.', ''),
                'description' => $row->getDescription(),
            ];
        }, $rows);

        return new Response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="niveau_jeux.json"',
        ]);
    }

    #[Route('/export/pdf', name: 'app_niveau_jeu_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, NiveauJeuRepository $niveauJeuRepository): Response
    {
        $search = $request->query->get('search');
        $niveau = $request->query->get('niveau');
        $tri = $request->query->get('tri', 'id_desc');

        $rows = $niveauJeuRepository->searchAndFilter($search, $niveau, $tri);
        $html = $this->renderView('niveau_jeu/pdf.html.twig', [
            'rows' => $rows,
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
            'Content-Disposition' => 'attachment; filename="niveau_jeux.pdf"',
        ]);
    }

    #[Route('/{id}/edit', name: 'app_niveau_jeu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NiveauJeu $niveauJeu, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NiveauJeuType::class, $niveauJeu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le niveau a été modifié avec succès.');

            return $this->redirectToRoute('app_niveau_jeu_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('niveau_jeu/edit.html.twig', [
            'niveau_jeu' => $niveauJeu,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_niveau_jeu_delete', methods: ['POST'])]
    public function delete(Request $request, NiveauJeu $niveauJeu, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $niveauJeu->getId(), $request->request->get('_token'))) {
            $entityManager->remove($niveauJeu);
            $entityManager->flush();

            $this->addFlash('success', 'Le niveau a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_niveau_jeu_index', [], Response::HTTP_SEE_OTHER);
    }
}