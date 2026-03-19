<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;

class ExcelReportService
{
    /**
     * Generate an Excel file from buckets and return the public URL in a grid layout.
     */
    public function generateReport(array $buckets): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Set Column Headers (Row 1)
        $sheet->setCellValue('B1', 'Inbound');
        $sheet->setCellValue('C1', 'Meeting');
        $sheet->setCellValue('D1', 'Deals');

        // 2. Define Rows (Stage Names/IDs)
        $stageRows = [
            '88b0c213-bc46-4d40-9a6a-f436adabf3cf' => ['name' => 'Stages Upcoming', 'col' => 'C'],
            '6ac9ad58-faa7-4a77-928d-fb08a83dd8c9' => ['name' => 'Reschedule', 'col' => 'C'],
            'b6bfcac3-14bb-46ed-9109-7f195156a9f5' => ['name' => 'Stages proposal out', 'col' => 'D'],
            '005b63c3-99e8-4b58-8294-bf9c2b2e96b4' => ['name' => 'Stalled', 'col' => 'D'],
            'd7778228-0900-4c7d-acc3-667f4bc73626' => ['name' => 'Discovery', 'col' => 'D'],
            '34b900db-61a4-49a5-895e-84c9f9f34795' => ['name' => 'Stages contacting', 'col' => 'B'],
            '54ac93bc-896c-452a-9b4b-292bca36df90' => ['name' => 'New connection', 'col' => 'B'],
        ];

        // 3. Initialize the Grid with labels in Column A
        $rowMapping = [];
        $currentRow = 2;
        foreach ($stageRows as $id => $info) {
            $sheet->setCellValue("A{$currentRow}", $info['name']);
            $rowMapping[$id] = $currentRow;
            $currentRow++;
        }

        // 4. Group all opportunities from buckets
        $allOpps = array_merge($buckets['Inbound'], $buckets['Meetings'], $buckets['Deals']);

        // 5. Populate the Grid
        $cellData = [];
        foreach ($allOpps as $opp) {
            $stageId = $opp['stageId'];
            if (isset($rowMapping[$stageId])) {
                $row = $rowMapping[$stageId];
                $col = $stageRows[$stageId]['col'];
                $cell = "{$col}{$row}";

                if (!isset($cellData[$cell])) {
                    $cellData[$cell] = [];
                }

                // Clean the name: Remove date patterns like "M/D/YYYY - " or "M.D.YYYY "
                $cleanName = preg_replace('/^(\d{1,2}[\/\.]\d{1,2}[\/\.]\d{4})\s*[-\s]*\s*/', '', $opp['name']);
                $cellData[$cell][] = '- ' . $cleanName;
            }
        }

        // Write gathered data to cells with newlines
        foreach ($cellData as $cell => $names) {
            $sheet->setCellValue($cell, implode("\n", $names));
            $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
        }

        // Format Column A
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(30);

        // 6. Save to public/reports
        $fileName = 'ghl_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filePath = public_path('reports/' . $fileName);
        
        if (!file_exists(public_path('reports'))) {
            mkdir(public_path('reports'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $fileUrl = url('reports/' . $fileName);
        $generatedAt = date('Y-m-d H:i:s');

        return [
            'url' => $fileUrl,
            'path' => $filePath,
            'filename' => $fileName,
            'date' => $generatedAt,
        ];
    }
}
