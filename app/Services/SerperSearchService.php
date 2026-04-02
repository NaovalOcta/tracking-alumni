<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Client\Pool;

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
            $this->tryHitRateLimit();

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

    /**
     * Search concurrently using Serper.dev.
     *
     * @param array $queries Array of query strings
     * @param int $maxResults
     * @return array Array of search results where key is query index
     */
    public function searchConcurrent(array $queries, int $maxResults = 3): array
    {
        if (!$this->isConfigured() || empty($queries)) {
            return [];
        }

        try {
            $this->tryHitRateLimit(count($queries));

            $responses = Http::pool(function (Pool $pool) use ($queries, $maxResults) {
                foreach ($queries as $query) {
                    $pool->as($query)->timeout(25)
                        ->withHeaders([
                            'X-API-KEY'    => $this->apiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post('https://google.serper.dev/search', [
                            'q'   => $query,
                            'num' => min($maxResults, 10),
                        ]);
                }
            });

            $results = [];
            foreach ($responses as $query => $response) {
                if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                    $data = $response->json();
                    $items = collect($data['organic'] ?? [])->map(function ($item) {
                        return [
                            'title'   => $item['title'] ?? '',
                            'link'    => $item['link'] ?? '',
                            'snippet' => $item['snippet'] ?? '',
                        ];
                    })->toArray();
                    $results[$query] = [
                        'items' => $items,
                        'totalResults' => (int) ($data['searchParameters']['totalResults'] ?? count($items)),
                    ];
                } else {
                    $errorMsg = $response instanceof \Exception ? $response->getMessage() : $response->body();
                    Log::error("SerperSearchService: API error for concurrent query", [
                        'query' => $query,
                        'error' => $errorMsg
                    ]);
                    $results[$query] = ['items' => [], 'totalResults' => 0];
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('SerperSearchService: Exception during concurrent search', [
                'message' => $e->getMessage(),
                'queries' => count($queries),
            ]);
            return [];
        }
    }

    /**
     * Implement strict rate limiting.
     */
    protected function tryHitRateLimit(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            // Daily limit check
            if (RateLimiter::tooManyAttempts('serper_search_day', $this->dailyLimit)) {
                throw new \Exception("Daily Serper limit ({$this->dailyLimit}) reached.");
            }
            RateLimiter::hit('serper_search_day', 86400); // 24 hours

            // Minute limit check (50 per minute)
            if (RateLimiter::tooManyAttempts('serper_search_minute', 50)) {
                $seconds = RateLimiter::availableIn('serper_search_minute');
                Log::warning("Serper minute limit reached. Sleeping for {$seconds} seconds.");
                sleep($seconds);
            }
            RateLimiter::hit('serper_search_minute', 60);
        }
    }
}
