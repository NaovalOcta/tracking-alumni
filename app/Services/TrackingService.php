<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\EvidenceLog;
use App\Models\SearchQuery;
use App\Models\TrackingResult;
use App\Models\TrackingHistory;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    protected QueryGeneratorService $queryGenerator;
    protected SerperSearchService $serperSearch;
    protected GeminiAnalysisService $geminiAnalysis;

    /**
     * Minimum evidence count from Tier 1 (LinkedIn) to skip remaining tiers.
     */
    protected int $earlyStopThreshold = 3;

    public function __construct(
        QueryGeneratorService $queryGenerator,
        SerperSearchService $serperSearch,
        GeminiAnalysisService $geminiAnalysis,
    ) {
        $this->queryGenerator = $queryGenerator;
        $this->serperSearch = $serperSearch;
        $this->geminiAnalysis = $geminiAnalysis;
    }

    /**
     * Track a single alumni: generate queries → search → analyze → store.
     *
     * @return array{status: string, message: string}
     */
    public function trackAlumni(Alumni $alumni): array
    {
        Log::info("TrackingService: Starting tracking for {$alumni->nim} - {$alumni->nama_lengkap}");

        // Mark as in-progress
        $alumni->update(['tracking_status' => 'sedang_dilacak']);

        // Step 1: Generate query variations
        $queryVariations = $this->queryGenerator->generate($alumni);

        if (empty($queryVariations)) {
            $alumni->update(['tracking_status' => 'insufficient_data']);
            return ['status' => 'insufficient_data', 'message' => 'Tidak cukup data untuk membuat query pencarian.'];
        }

        // Step 2: Execute searches with early-stop logic
        $allEvidence = [];
        $tier1Evidence = 0;
        $currentTier = null;
        $skippedTiers = false;

        foreach ($queryVariations as $queryInfo) {
            // Early-stop: if we already have enough LinkedIn evidence, skip non-LinkedIn tiers
            if ($tier1Evidence >= $this->earlyStopThreshold && $queryInfo['tier'] !== 'tier1_linkedin') {
                if (!$skippedTiers) {
                    Log::info("TrackingService: Early-stop — {$tier1Evidence} LinkedIn evidence found, skipping remaining tiers for {$alumni->nim}");
                    $skippedTiers = true;
                }
                continue;
            }

            $searchResults = $this->serperSearch->search($queryInfo['query'], 3);

            // Log the search query
            SearchQuery::create([
                'alumni_nim'    => $alumni->nim,
                'query_text'    => $queryInfo['query'],
                'search_tier'   => $queryInfo['tier'],
                'results_count' => $searchResults['totalResults'],
                'searched_at'   => now(),
            ]);

            // Collect evidence items
            foreach ($searchResults['items'] as $item) {
                $allEvidence[] = [
                    'source_url'  => $item['link'],
                    'raw_snippet' => $item['snippet'],
                    'source_type' => $this->mapTierToSourceType($queryInfo['tier'], $item['link']),
                ];

                // Track tier 1 evidence count for early-stop
                if ($queryInfo['tier'] === 'tier1_linkedin') {
                    $tier1Evidence++;
                }
            }

            // Avoid hammering the API — tiny delay between queries
            usleep(200_000); // 200ms
        }

        if (empty($allEvidence)) {
            $alumni->update([
                'tracking_status' => 'not_found',
                'last_tracked_at' => now(),
            ]);
            return ['status' => 'not_found', 'message' => 'Tidak ditemukan evidence dari pencarian web.'];
        }

        // Save evidence logs
        foreach ($allEvidence as $evidence) {
            EvidenceLog::create([
                'alumni_nim'  => $alumni->nim,
                'source_type' => $evidence['source_type'],
                'source_url'  => $evidence['source_url'],
                'raw_snippet' => $evidence['raw_snippet'],
                'searched_at' => now(),
            ]);
        }

        // Step 3: Analyze with Gemini AI
        $alumniData = [
            'nim'           => $alumni->nim,
            'nama_lengkap'  => $alumni->nama_lengkap,
            'nama_variasi'  => $alumni->nama_variasi ?? [],
            'prodi'         => $alumni->prodi,
            'tahun_lulus'   => $alumni->tahun_lulus,
        ];

        $analysis = $this->geminiAnalysis->analyze($allEvidence, $alumniData);
        $confidence = $analysis['confidence'];

        // Step 4: Determine tracking status based on thresholds
        $autoVerifyThreshold = config('scoutalumni.tracking.auto_verify_threshold', 0.8);
        $needsAuditThreshold = config('scoutalumni.tracking.needs_audit_threshold', 0.5);

        if ($confidence >= $autoVerifyThreshold) {
            $newStatus = 'auto_verified';
        } elseif ($confidence >= $needsAuditThreshold) {
            $newStatus = 'needs_audit';
        } else {
            $newStatus = 'not_found';
        }

        // Step 5: Save tracking result
        TrackingResult::updateOrCreate(
            ['alumni_nim' => $alumni->nim],
            [
                'jabatan'          => $analysis['jabatan'],
                'instansi'         => $analysis['instansi'],
                'bidang_pekerjaan' => $analysis['bidang_pekerjaan'],
                'lokasi'           => $analysis['lokasi'],
                'linkedin_url'     => $analysis['linkedin_url'],
                'confidence_score' => $confidence,
                'ai_notes'         => $analysis['notes'],
                'source_type'      => 'serper_gemini',
            ]
        );

        // Step 6: Save tracking history snapshot
        TrackingHistory::create([
            'alumni_nim'    => $alumni->nim,
            'snapshot_data' => $analysis,
            'changed_reason' => 'Tracking otomatis - confidence: ' . round($confidence * 100) . '%',
            'created_at'    => now(),
        ]);

        // Step 7: Update alumni status
        $alumni->update([
            'tracking_status' => $newStatus,
            'last_tracked_at' => now(),
        ]);

        Log::info("TrackingService: Completed {$alumni->nim} — status: {$newStatus}, confidence: {$confidence}");

        return [
            'status'     => $newStatus,
            'message'    => "Tracking selesai. Status: {$newStatus}, Confidence: " . round($confidence * 100) . '%',
            'confidence' => $confidence,
        ];
    }

    /**
     * Check if required APIs are configured.
     */
    public function isReady(): array
    {
        return [
            'serper' => $this->serperSearch->isConfigured(),
            'gemini' => $this->geminiAnalysis->isConfigured(),
            'ready'  => $this->serperSearch->isConfigured() && $this->geminiAnalysis->isConfigured(),
        ];
    }

    /**
     * Map query tier to evidence_logs source_type ENUM value.
     * Uses the URL to determine the most accurate source type.
     */
    protected function mapTierToSourceType(string $tier, string $url): string
    {
        // Try to determine source type from URL first
        if (str_contains($url, 'linkedin.com')) {
            return 'linkedin';
        }
        if (str_contains($url, 'scholar.google')) {
            return 'google_scholar';
        }
        if (str_contains($url, 'github.com')) {
            return 'github';
        }

        // Fallback based on tier
        return match ($tier) {
            'tier1_linkedin'       => 'linkedin',
            'tier2_scholar_github' => 'google_scholar',
            'tier3_news_web'       => 'website',
            default                => 'other',
        };
    }
}
