<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerperSearchService
{
    protected string $apiKey;
    protected int $dailyLimit;

    public function __construct()
    {
        $this->apiKey = config('scoutalumni.serper.api_key', '');
        $this->dailyLimit = config('scoutalumni.serper.daily_limit', 250);
    }

    /**
     * Check if the service is configured with a valid API key.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Search using Serper.dev Google Search API.
     *
     * @return array{items: array, totalResults: int}
     */
    public function search(string $query, int $maxResults = 5): array
    {
        if (!$this->isConfigured()) {
            Log::warning('SerperSearchService: API key not configured.');
            return ['items' => [], 'totalResults' => 0];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-KEY'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://google.serper.dev/search', [
                    'q'   => $query,
                    'num' => min($maxResults, 10),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Serper returns results in 'organic' array
                $items = collect($data['organic'] ?? [])->map(function ($item) {
                    return [
                        'title'   => $item['title'] ?? '',
                        'link'    => $item['link'] ?? '',
                        'snippet' => $item['snippet'] ?? '',
                    ];
                })->toArray();

                // Total results from searchParameters or count of organic
                $totalResults = (int) ($data['searchParameters']['totalResults'] ?? count($items));

                return [
                    'items'        => $items,
                    'totalResults' => $totalResults,
                ];
            }

            Log::error('SerperSearchService: API returned error', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'query'  => $query,
            ]);

            return ['items' => [], 'totalResults' => 0];
        } catch (\Exception $e) {
            Log::error('SerperSearchService: Exception during search', [
                'message' => $e->getMessage(),
                'query'   => $query,
            ]);

            return ['items' => [], 'totalResults' => 0];
        }
    }
}
