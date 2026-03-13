<?php

namespace App\Services;

use App\Models\Alumni;

class QueryGeneratorService
{
    protected GeminiAnalysisService $geminiAnalysis;

    /**
     * Maximum number of search queries per alumni.
     */
    protected int $maxQueries = 8;

    public function __construct(GeminiAnalysisService $geminiAnalysis)
    {
        $this->geminiAnalysis = $geminiAnalysis;
    }

    /**
     * Generate optimized search query variations and contextual keywords for an alumni.
     *
     * @param  Alumni  $alumni
     * @return array{queries: array<int, array{query: string, tier: string}>, context_keywords: string[]}
     */
    public function generate(Alumni $alumni): array
    {
        // 1. Try to get strategy from Gemini AI
        $strategy = $this->geminiAnalysis->generateTrackingStrategy($alumni);

        // 2. If Gemini returned queries, use them (merged with basic prodi/fakultas context)
        if (!empty($strategy['queries'])) {
            return [
                'queries' => array_slice($strategy['queries'], 0, $this->maxQueries),
                'context_keywords' => $strategy['context_keywords']
            ];
        }

        // 3. Fallback to static logic if Gemini fails
        return [
            'queries' => $this->generateStaticQueries($alumni),
            'context_keywords' => [
                strtolower($alumni->prodi),
                strtolower($alumni->fakultas),
            ]
        ];
    }

    /**
     * Static fallback logic for query generation.
     */
    protected function generateStaticQueries(Alumni $alumni): array
    {
        $queries = [];
        $namaLengkap = $alumni->nama_lengkap;
        $prodi = $alumni->prodi;
        $fakultas = $alumni->fakultas;

        $namaVariasi = $alumni->nama_variasi ?? [];
        $namaVariasi = array_values(array_unique(array_filter($namaVariasi, function ($v) use ($namaLengkap) {
            return $v !== $namaLengkap;
        })));

        // Tier 1: LinkedIn
        $queries[] = [
            'query' => "\"{$namaLengkap}\" site:linkedin.com/in",
            'tier'  => 'tier1_linkedin',
        ];

        if (!empty($namaVariasi)) {
            $queries[] = [
                'query' => "\"{$namaVariasi[0]}\" site:linkedin.com/in",
                'tier'  => 'tier1_linkedin',
            ];
        }

        $queries[] = [
            'query' => "\"{$namaLengkap}\" \"{$prodi}\" site:linkedin.com",
            'tier'  => 'tier1_linkedin',
        ];

        // Tier 2: Academic
        $queries[] = [
            'query' => "\"{$namaLengkap}\" site:scholar.google.com",
            'tier'  => 'tier2_scholar_github',
        ];

        // Tier 3: General
        $queries[] = [
            'query' => "\"{$namaLengkap}\" \"{$prodi}\"",
            'tier'  => 'tier3_news_web',
        ];

        if ($fakultas) {
            $queries[] = [
                'query' => "\"{$namaLengkap}\" \"{$fakultas}\"",
                'tier'  => 'tier3_news_web',
            ];
        }

        $queries[] = [
            'query' => "\"{$namaLengkap}\" site:github.com",
            'tier'  => 'tier2_scholar_github',
        ];

        return array_slice($queries, 0, $this->maxQueries);
    }
}
