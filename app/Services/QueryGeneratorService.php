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

        // Tier 1: LinkedIn + Anchor UMM
        $queries[] = [
            'query' => "\"{$namaLengkap}\" \"Universitas Muhammadiyah Malang\" site:linkedin.com/in",
            'tier'  => 'tier1_linkedin',
        ];

        // Tier 2: LinkedIn Broad
        $queries[] = [
            'query' => "\"{$namaLengkap}\" \"UMM\" OR \"Muhammadiyah Malang\" site:linkedin.com/in",
            'tier'  => 'tier1_linkedin',
        ];

        // Tier 3: Web Umum + Portal Berita
        $queries[] = [
            'query' => "\"{$namaLengkap}\" \"UMM\" OR \"Muhammadiyah Malang\" alumni",
            'tier'  => 'tier4_web',
        ];

        // Tier 4: Prodi-specific
        $queries[] = [
            'query' => "\"{$namaLengkap}\" \"{$prodi}\" \"Malang\"",
            'tier'  => 'tier4_web',
        ];

        if (!empty($namaVariasi)) {
            $queries[] = [
                'query' => "\"{$namaVariasi[0]}\" site:linkedin.com/in",
                'tier'  => 'tier1_linkedin',
            ];
        }

        return array_slice($queries, 0, $this->maxQueries);
    }

    /**
     * Generate queries for Phase B: Social Media Discovery.
     */
    public function generateSocialMediaQueries(string $namaLengkap, ?string $linkedinUsername, ?string $instansi, ?string $lokasi): array
    {
        $queries = [];

        // Technique 1: LinkedIn Username -> Cross-Platform Search
        if (!empty($linkedinUsername)) {
            $queries[] = [
                'query' => "\"{$linkedinUsername}\" site:instagram.com OR site:tiktok.com OR site:facebook.com",
                'tier'  => 'tier2_ig_tiktok',
            ];
        }

        // Technique 2: Identity-Enriched Social Search
        $contextualInfo = [];
        if (!empty($instansi)) {
            $contextualInfo[] = "\"{$instansi}\"";
        }
        if (!empty($lokasi)) {
            $contextualInfo[] = "\"{$lokasi}\"";
        }
        $contextStr = implode(' OR ', $contextualInfo);

        if (!empty($contextStr)) {
            $queries[] = [
                'query' => "\"{$namaLengkap}\" ({$contextStr}) site:instagram.com OR site:facebook.com OR site:tiktok.com",
                'tier'  => 'tier2_ig_tiktok',
            ];
        }

        // Technique 3: Facebook Real-Name Search + Bio Aggregator
        $queries[] = [
            'query' => "\"{$namaLengkap}\" site:facebook.com " . (!empty($lokasi) ? "\"{$lokasi}\"" : "\"Malang\""),
            'tier'  => 'tier3_facebook',
        ];

        $queries[] = [
            'query' => "\"{$namaLengkap}\" site:linktr.ee OR site:linkin.bio",
            'tier'  => 'tier4_web',
        ];

        // Limit to 3 parallel requests max to save API limits
        return array_slice($queries, 0, 3);
    }
    /**
     * Generate 12 unique queries for the Exhaustive OSINT Search.
     * Matrix: 3 Keywords x 4Platforms.
     */
    public function generateExhaustiveQueries(Alumni $alumni): array
    {
        $name = $alumni->nama_lengkap;
        // Clean special chars for better query performance
        $cleanName = preg_replace('/[^A-Za-z0-9 ]/', '', $name);
        $prodi = $alumni->prodi;

        $keywordVariants = [
            "\"{$name}\" \"UMM\" OR \"Universitas Muhammadiyah Malang\"",
            "\"{$name}\"",
            "\"{$cleanName}\" \"{$prodi}\" \"Malang\"",
        ];

        $platformConstraints = [
            "site:linkedin.com/in",
            "site:instagram.com",
            "site:facebook.com",
            "", // general web
        ];

        $exhaustiveQueries = [];
        foreach ($keywordVariants as $kw) {
            foreach ($platformConstraints as $plt) {
                $qStr = trim("{$kw} {$plt}");
                
                // Map platform to tier for database consistency
                $tier = 'tier4_web';
                if (str_contains($plt, 'linkedin')) $tier = 'tier1_linkedin';
                elseif (str_contains($plt, 'instagram') || str_contains($plt, 'facebook')) $tier = 'tier2_ig_tiktok';

                $exhaustiveQueries[] = [
                    'query' => $qStr,
                    'tier'  => $tier,
                ];
            }
        }

        return $exhaustiveQueries;
    }
}
