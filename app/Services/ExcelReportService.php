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
    /**
     * Generate an Excel file with a hierarchical format: Group -> Stage -> Leads.
     */
    public function generateReport(array $buckets): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Column Titles & Widths
        $sheet->setCellValue('A1', 'Category / Stage / Opportunity');
        
        $sheet->getColumnDimension('A')->setWidth(70);

        // Header Styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
        ];
        $sheet->getStyle('A1')->applyFromArray($headerStyle);

        // 2. Define Category Labels and Stage Mapping
        $categories = [
            'Inbound' => ['label' => 'Inbound', 'color' => 'E9EBEE'],
            'Meetings' => ['label' => 'Meeting', 'color' => 'E9EBEE'],
            'Deals' => ['label' => 'Deals', 'color' => 'E9EBEE'],
        ];

        $stageNames = [
            '54ac93bc-896c-452a-9b4b-292bca36df90' => 'New connection',
            '34b900db-61a4-49a5-895e-84c9f9f34795' => 'Stages contacting',
            '88b0c213-bc46-4d40-9a6a-f436adabf3cf' => 'Stages Upcoming',
            '6ac9ad58-faa7-4a77-928d-fb08a83dd8c9' => 'Reschedule',
            'd7778228-0900-4c7d-acc3-667f4bc73626' => 'Discovery',
            'b6bfcac3-14bb-46ed-9109-7f195156a9f5' => 'Stages proposal out',
            '005b63c3-99e8-4b58-8294-bf9c2b2e96b4' => 'Stalled',
        ];

        $currentRow = 2;

        // 3. Populate Hierarchical List
        foreach ($categories as $bucketKey => $catInfo) {
            $leadsInBucket = $buckets[$bucketKey] ?? [];
            if (empty($leadsInBucket)) continue;

            // --- Level 0: Category (e.g. Deals) ---
            $sheet->setCellValue("A{$currentRow}", $catInfo['label']);
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($catInfo['color']);
            
            $currentRow++;

            // Group leads by stage within this bucket
            $groupedByStage = [];
            foreach ($leadsInBucket as $lead) {
                $groupedByStage[$lead['stageId']][] = $lead;
            }

            foreach ($groupedByStage as $stageId => $leads) {
                $stageName = $stageNames[$stageId] ?? 'Other';

                // --- Level 1: Stage (e.g. Proposal Out) ---
                $sheet->setCellValue("A{$currentRow}", $stageName);
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setIndent(1);
                $sheet->getStyle("A{$currentRow}")->getFont()->setItalic(true);
                $sheet->getStyle("A{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FA');
                
                $sheet->getRowDimension($currentRow)->setOutlineLevel(1)->setVisible(true)->setCollapsed(false);
                $currentRow++;

                foreach ($leads as $lead) {
                    // --- Level 2: Opportunity (Lead) ---
                    $cleanName = preg_replace('/^(\d{1,2}[\/\.]\d{1,2}[\/\.]\d{4})\s*[-\s]*\s*/', '', $lead['name']);
                    
                    $sheet->setCellValue("A{$currentRow}", $cleanName);
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setIndent(2);
                    
                    // Add grouping (Level 2 leads are children of Stage)
                    $sheet->getRowDimension($currentRow)->setOutlineLevel(2)->setVisible(true)->setCollapsed(false);
                    $currentRow++;
                }
            }
        }

        // Configure Summary Location (puts the Group toggle buttons on Top)
        $sheet->setShowSummaryBelow(false);
        $sheet->setShowSummaryRight(false);

        // Add AutoFilter (provides the dropdowns you requested)
        $sheet->setAutoFilter("A1:A" . ($currentRow - 1));


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
