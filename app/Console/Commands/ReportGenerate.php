<?php

namespace App\Console\Commands;

use App\Services\GHLService;
use App\Services\ExcelReportService;
use Illuminate\Console\Command;

class ReportGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a daily GHL Opportunity report as XLS and notify webhook';

    /**
     * Execute the console command.
     */
    public function handle(GHLService $ghlService, ExcelReportService $excelService)
    {
        $this->info('Starting GHL Opportunity Report generation...');

        $pipelineIds = [
            'LqHQuHXIAegdio77yaNN',
            'eP52vVtDMYpswKF5C9XO',
            'u1DDrPskc0ZOToPgcpOJ'
        ];

        try {
            // 1. Fetch Opportunities
            $this->info('Fetching opportunities from GHL (Search API)...');
            $opportunities = $ghlService->fetchOpportunities($pipelineIds);
            
            if ($opportunities->isEmpty()) {
                $this->warn('No opportunities found for the specified pipelines.');
            }

            // 2. Map to Buckets
            $this->info('Categorizing opportunities...');
            $buckets = $ghlService->mapToBuckets($opportunities);

            // 3. Generate XLS Report
            $this->info('Generating Excel report...');
            $report = $excelService->generateReport($buckets);
            $this->info("Report generated: {$report['url']}");

            // 4. Notify Webhook
            $totalCount = count($buckets['Inbound']) + count($buckets['Meetings']) + count($buckets['Deals']);
            $this->info("Notifying webhook with total count: {$totalCount}");
            $ghlService->notifyWebhook($totalCount, $report['url'], $report['date']);

            $this->info('Report generated and webhook notified successfully!');
        } catch (\Exception $e) {
            $this->error('An error occurred during report generation: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
