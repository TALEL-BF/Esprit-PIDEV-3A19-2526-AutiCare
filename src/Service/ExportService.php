<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Generate a PDF response from HTML content
     */
    public function generatePdf(string $html, string $filename): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        $response = new Response($output);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename . '.pdf'
        );

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Generate an Excel response from data
     */
    public function generateExcel(array $headings, array $data, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add headings
        $column = 'A';
        foreach ($headings as $heading) {
            $sheet->setCellValue($column . '1', $heading);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
            $column++;
        }

        // Add data
        $rowNumber = 2;
        foreach ($data as $rowData) {
            $column = 'A';
            foreach ($rowData as $cellValue) {
                $sheet->setCellValue($column . $rowNumber, $cellValue);
                $column++;
            }
            $rowNumber++;
        }

        // Auto-size columns
        $lastColumn = $sheet->getHighestColumn();
        $currentColumn = 'A';
        while ($currentColumn <= $lastColumn) {
            $sheet->getColumnDimension($currentColumn)->setAutoSize(true);
            if ($currentColumn == $lastColumn) break;
            $currentColumn++;
        }

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename . '.xlsx'
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
