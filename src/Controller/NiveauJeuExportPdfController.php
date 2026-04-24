<?php

namespace App\Controller;

use App\Repository\NiveauJeuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class NiveauJeuExportPdfController extends AbstractController
{
    #[Route('/niveau/jeu/export/pdf', name: 'app_niveau_jeu_export_pdf', methods: ['GET'])]
    public function exportPdf(NiveauJeuRepository $niveauJeuRepository): Response
    {
        $niveaux = $niveauJeuRepository->findAll();
        $html = $this->renderView('niveau_jeu/export_pdf.html.twig', [
            'niveau_jeus' => $niveaux,
        ]);
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="niveau_jeu.pdf"',
        ]);
    }
}
