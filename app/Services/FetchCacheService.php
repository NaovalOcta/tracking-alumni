<?php

namespace App\Services;

use App\Models\FetchCache;
use App\Models\QueryHash;

class FetchCacheService
{
    /**
     * Check if a URL has a valid, unexpired cache entry.
     *
     * @param string $url
     * @return bool
     */
    public function hasCachedUrl(string $url): bool
    {
        $hash = hash('sha256', $url);

        return FetchCache::where('url_hash', $hash)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get the cached content blocks for a given URL.
     *
     * @param string $url
     * @return array|null
     */
    public function getCachedContent(string $url): ?array
    {
        $hash = hash('sha256', $url);

        $cache = FetchCache::where('url_hash', $hash)
            ->where('expires_at', '>', now())
            ->first();

        return $cache ? $cache->content_blocks : null;
    }

    /**
     * Cache the content of a URL for exactly 30 days.
     *
     * @param string $url
     * @param array $contentBlocks
     * @return FetchCache
     */
    public function cacheUrl(string $url, array $contentBlocks): FetchCache
    {
        $hash = hash('sha256', $url);

        return FetchCache::updateOrCreate(
            ['url_hash' => $hash],
            [
                'original_url' => $url,
                'content_blocks' => $contentBlocks,
                'expires_at' => now()->addDays(30),
            ]
        );
    }

    /**
     * Normalize the query string to lowercase and trimmed.
     *
     * @param string $query
     * @return string
     */
    private function normalizeQuery(string $query): string
    {
        return strtolower(trim($query));
    }

    /**
     * Check if the query has already been executed on the same calendar day.
     *
     * @param string $query
     * @return bool
     */
    public function isQueryDuplicate(string $query): bool
    {
        $normalizedQuery = $this->normalizeQuery($query);
        $hash = hash('sha256', $normalizedQuery);

        return QueryHash::where('query_hash', $hash)
            ->whereDate('executed_at', now()->toDateString())
            ->exists();
    }

    /**
     * Get the cached query execution record for the same calendar day.
     *
     * @param string $query
     * @return QueryHash|null
     */
    public function getCachedQueryResult(string $query): ?QueryHash
    {
        $normalizedQuery = $this->normalizeQuery($query);
        $hash = hash('sha256', $normalizedQuery);

        return QueryHash::where('query_hash', $hash)
            ->whereDate('executed_at', now()->toDateString())
            ->first();
    }

    /**
     * Record a new execution for a given query.
     *
     * @param string $query
     * @param int $resultCount
     * @return QueryHash
     */
    public function recordQuery(string $query, int $resultCount): QueryHash
    {
        $normalizedQuery = $this->normalizeQuery($query);
        $hash = hash('sha256', $normalizedQuery);

        return QueryHash::create([
            'query_hash' => $hash,
            'original_query' => $normalizedQuery,
            'result_count' => $resultCount,
            'executed_at' => now(),
        ]);
    }
}
