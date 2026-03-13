<?php

namespace App\Services;

use App\Models\Alumni;

class QueryGeneratorService
{
    /**
     * Maximum number of search queries per alumni.
     */
    protected int $maxQueries = 8;

    /**
     * Generate optimized search query variations for an alumni.
     *
     * Strategy:
     * - Tier 1 (LinkedIn): nama utama + 1 variasi terbaik (paling berdampak)
     * - Tier 2 (Akademik): hanya nama utama untuk scholar
     * - Tier 3 (General):  nama utama + konteks prodi/universitas
     *
     * Target: ~6-8 queries (turun dari ~26)
     *
     * @return array<int, array{query: string, tier: string}>
     */
    public function generate(Alumni $alumni): array
    {
        $queries = [];

        $namaLengkap = $alumni->nama_lengkap;
        $prodi = $alumni->prodi;
        $fakultas = $alumni->fakultas;

        // Collect name variations (excluding the main name)
        $namaVariasi = $alumni->nama_variasi ?? [];
        $namaVariasi = array_values(array_unique(array_filter($namaVariasi, function ($v) use ($namaLengkap) {
            return $v !== $namaLengkap;
        })));

        // --- TIER 1: LinkedIn (most important for career tracking) ---
        // Main name on LinkedIn
        $queries[] = [
            'query' => "\"{$namaLengkap}\" site:linkedin.com/in",
            'tier'  => 'tier1_linkedin',
        ];

        // Best variation on LinkedIn (only the first/most different one)
        if (!empty($namaVariasi)) {
            $queries[] = [
                'query' => "\"{$namaVariasi[0]}\" site:linkedin.com/in",
                'tier'  => 'tier1_linkedin',
            ];
        }

        // LinkedIn with prodi context (helps disambiguate common names)
        $queries[] = [
            'query' => "\"{$namaLengkap}\" \"{$prodi}\" site:linkedin.com",
            'tier'  => 'tier1_linkedin',
        ];

        // --- TIER 2: Academic (Scholar) ---
        $queries[] = [
            'query' => "\"{$namaLengkap}\" site:scholar.google.com",
            'tier'  => 'tier2_scholar_github',
        ];

        // --- TIER 3: General Web (contextual, not brute-force) ---
        // Name + prodi (strongest context signal)
        $queries[] = [
            'query' => "\"{$namaLengkap}\" \"{$prodi}\"",
            'tier'  => 'tier3_news_web',
        ];

        // Name + fakultas (broader context)
        if ($fakultas) {
            $queries[] = [
                'query' => "\"{$namaLengkap}\" \"{$fakultas}\"",
                'tier'  => 'tier3_news_web',
            ];
        }

        // Name + GitHub (useful for tech alumni)
        $queries[] = [
            'query' => "\"{$namaLengkap}\" site:github.com",
            'tier'  => 'tier2_scholar_github',
        ];

        // Enforce max query cap
        return array_slice($queries, 0, $this->maxQueries);
    }
}
