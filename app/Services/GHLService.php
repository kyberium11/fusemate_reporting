<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class GHLService
{
    protected string $apiKey;
    protected string $locationId;
    protected string $baseUrl = 'https://services.leadconnectorhq.com';

    public function __construct()
    {
        $this->apiKey = config('services.ghl.api_key');
        $this->locationId = config('services.ghl.location_id');
    }

    /**
     * Fetch opportunities for multiple pipeline IDs using the search endpoint.
     */
    public function fetchOpportunities(array $pipelineIds): Collection
    {
        $allOpportunities = collect();

        foreach ($pipelineIds as $pipelineId) {
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Version' => '2021-07-28',
            ])->get("{$this->baseUrl}/opportunities/search", [
                'location_id' => $this->locationId,
                'pipeline_id' => $pipelineId,
                'status' => 'open', // Defaulting to open, can be adjusted
                'limit' => 100,
            ]);

            if ($response->successful()) {
                $opportunities = $response->json('opportunities', []);
                $allOpportunities = $allOpportunities->concat($opportunities);
            }
        }

        return $allOpportunities;
    }

    /**
     * Map opportunities into buckets based on stage IDs.
     */
    public function mapToBuckets(Collection $opportunities): array
    {
        $buckets = [
            'Inbound' => [],
            'Meetings' => [],
            'Deals' => [],
        ];

        $inboundStageIds = [
            '54ac93bc-896c-452a-9b4b-292bca36df90',
            '34b900db-61a4-49a5-895e-84c9f9f34795'
        ];

        $meetingsStageIds = [
            '88b0c213-bc46-4d40-9a6a-f436adabf3cf',
            '6ac9ad58-faa7-4a77-928d-fb08a83dd8c9'
        ];

        $dealsStageIds = [
            'd7778228-0900-4c7d-acc3-667f4bc73626',
            'b6bfcac3-14bb-46ed-9109-7f195156a9f5',
            '005b63c3-99e8-4b58-8294-bf9c2b2e96b4'
        ];

        foreach ($opportunities as $opportunity) {
            $stageId = $opportunity['pipelineStageId'] ?? null;
            $name = $opportunity['name'] ?? 'Unknown';

            $data = [
                'name' => $name,
                'stageId' => $stageId,
            ];

            if (in_array($stageId, $inboundStageIds)) {
                $buckets['Inbound'][] = $data;
            } elseif (in_array($stageId, $meetingsStageIds)) {
                $buckets['Meetings'][] = $data;
            } elseif (in_array($stageId, $dealsStageIds)) {
                $buckets['Deals'][] = $data;
            }
        }

        return $buckets;
    }

    /**
     * Notify GHL Webhook.
     */
    public function notifyWebhook(int $totalCount, string $fileUrl, string $date): void
    {
        $webhookUrl = config('services.ghl.webhook_url');

        if (!$webhookUrl) {
            return;
        }

        Http::post($webhookUrl, [
            'summary_count' => $totalCount,
            'report_url' => $fileUrl,
            'generated_at' => $date,
        ]);
    }
}
