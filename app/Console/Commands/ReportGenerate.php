<?php

namespace App\Console\Commands;

use App\Services\GHLService;
use App\Services\ExcelReportService;
use App\Services\ReportingService;
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
    public function handle(ReportingService $reportingService)
    {
        $this->info('Starting GHL Opportunity Report generation via Service...');

        try {
            $result = $reportingService->run();
            
            $this->info("Report generated: {$result['report_url']}");
            $this->info("Total opportunities: {$result['total_opportunities']}");
            $this->info('Report generated and webhook notified successfully!');
        } catch (\Exception $e) {
            $this->error('An error occurred during report generation: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
