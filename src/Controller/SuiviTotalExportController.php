<?php

namespace App\Controller;

use App\Entity\SuiviTotal;
use App\Repository\SuiviTotalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SuiviTotalExportController extends AbstractController
{
    #[Route('/suivi/total/export/csv', name: 'app_suivi_total_export_csv', methods: ['GET'])]
    public function exportCsv(SuiviTotalRepository $suiviTotalRepository): Response
    {
        $suivis = $suiviTotalRepository->findAll();
        $handle = fopen('php://memory', 'r+');
        fputcsv($handle, ['ID', 'ENFANT', 'MOYENNE', 'REMARQUE', 'NIVEAU']);
        foreach ($suivis as $suivi) {
            fputcsv($handle, [
                $suivi->getId(),
                $suivi->getEnfantId(),
                $suivi->getMoyenne(),
                $suivi->getRemarque(),
                $suivi->getNiveauJeu() ? $suivi->getNiveauJeu()->getLibelle() : '-',
            ]);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        return new Response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="suivi_total.csv"',
        ]);
    }
}
