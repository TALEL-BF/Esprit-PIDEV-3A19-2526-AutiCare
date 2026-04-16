<?php

namespace App\Controller;

use App\Entity\NiveauJeu;
use App\Repository\NiveauJeuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NiveauJeuExportController extends AbstractController
{
    #[Route('/niveau/jeu/export/csv', name: 'app_niveau_jeu_export_csv', methods: ['GET'])]
    public function exportCsv(NiveauJeuRepository $niveauJeuRepository): Response
    {
        $niveaux = $niveauJeuRepository->findAll();
        $handle = fopen('php://memory', 'r+');
        fputcsv($handle, ['ID', 'Libellé', 'Min', 'Max', 'Description']);
        foreach ($niveaux as $niveau) {
            fputcsv($handle, [
                $niveau->getId(),
                $niveau->getLibelle(),
                $niveau->getMinMoyenne(),
                $niveau->getMaxMoyenne(),
                $niveau->getDescription(),
            ]);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        return new Response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="niveau_jeu.csv"',
        ]);
    }
}
