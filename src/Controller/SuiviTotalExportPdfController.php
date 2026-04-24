<?php

namespace App\Controller;

use App\Entity\SuiviTotal;
use App\Repository\SuiviTotalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class SuiviTotalExportPdfController extends AbstractController
{
    #[Route('/suivi/total/export/pdf', name: 'app_suivi_total_export_pdf', methods: ['GET'])]
    public function exportPdf(SuiviTotalRepository $suiviTotalRepository): Response
    {
        $suivis = $suiviTotalRepository->findAll();
        $html = $this->renderView('suivi_total/export_pdf.html.twig', [
            'suivi_totals' => $suivis,
        ]);
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="suivi_total.pdf"',
        ]);
    }
}
