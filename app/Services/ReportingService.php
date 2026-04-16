<?php

namespace App\Services;

class ReportingService
{
    protected GHLService $ghlService;
    protected ExcelReportService $excelService;

    public function __construct(GHLService $ghlService, ExcelReportService $excelService)
    {
        $this->ghlService = $ghlService;
        $this->excelService = $excelService;
    }

    /**
     * Run the full reporting process.
     */
    public function run()
    {
        $pipelineIds = [
            'LqHQuHXIAegdio77yaNN',
            'eP52vVtDMYpswKF5C9XO',
            'u1DDrPskc0ZOToPgcpOJ'
        ];

        // 1. Fetch
        $opportunities = $this->ghlService->fetchOpportunities($pipelineIds);
        
        // 2. Map
        $buckets = $this->ghlService->mapToBuckets($opportunities);

        // 3. Generate
        $report = $this->excelService->generateReport($buckets);

        // 4. Notify (Optional callback to GHL)
        $totalCount = count($buckets['Inbound']) + count($buckets['Meetings']) + count($buckets['Deals']);
        $this->ghlService->notifyWebhook($totalCount, $report['url'], $report['date']);

        return [
            'status' => 'success',
            'report_url' => $report['url'],
            'total_opportunities' => $totalCount,
            'generated_at' => $report['date'],
            'filename' => $report['filename']
        ];
    }
}
