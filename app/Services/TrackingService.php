<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\EvidenceLog;
use App\Models\SearchQuery;
use App\Models\TrackingResult;
use App\Models\TrackingHistory;
use App\Events\TrackingProgressUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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

        $maxRetries = 2;
        $currentRetry = 0;
        $bestAnalysis = null;
        $allEvidenceFinal = [];

        $alumniData = [
            'nim'           => $alumni->nim,
            'nama_lengkap'  => $alumni->nama_lengkap,
            'nama_variasi'  => $alumni->nama_variasi ?? [],
            'prodi'         => $alumni->prodi,
            'tahun_lulus'   => $alumni->tahun_lulus,
        ];

        while ($currentRetry <= $maxRetries) {
            $this->updateProgress($alumni->nim, 10 + ($currentRetry * 5), "Merumuskan strategi pencarian (Percobaan {$currentRetry})...");
            
            try {
                $strategy = $this->queryGenerator->generate($alumni);
                $queries = $strategy['queries'] ?? [];
            } catch (\Exception $e) {
                Log::error("TrackingService: Strategy generation failed: " . $e->getMessage());
                $queries = [];
            }

            // Mutation logic for retries: broaden queries
            if ($currentRetry > 0) {
                foreach ($queries as &$q) {
                    $q['query'] = str_replace("\"{$alumni->prodi}\"", "", $q['query']);
                    $q['query'] = str_replace(strtolower($alumni->prodi), "", strtolower($q['query']));
                }
            }

            if (empty($queries)) {
                break;
            }

            $queryStrings = array_unique(array_column($queries, 'query'));
            $this->updateProgress($alumni->nim, 30, "Mengeksekusi " . count($queryStrings) . " query paralel...");
            
            $concurrentResults = $this->serperSearch->searchConcurrent($queryStrings, 3);
            
            $allEvidence = [];
            foreach ($queries as $queryInfo) {
                $qStr = $queryInfo['query'];
                $searchResults = $concurrentResults[$qStr] ?? ['items' => [], 'totalResults' => 0];

                SearchQuery::create([
                    'alumni_nim'    => $alumni->nim,
                    'query_text'    => $qStr,
                    'search_tier'   => $queryInfo['tier'],
                    'results_count' => $searchResults['totalResults'],
                    'searched_at'   => now(),
                ]);

                foreach ($searchResults['items'] as $item) {
                    // Deduplicate URLs
                    if (!collect($allEvidence)->contains('source_url', $item['link'])) {
                        $allEvidence[] = [
                            'source_url'  => $item['link'],
                            'raw_snippet' => $item['snippet'] . ' - ' . $item['title'],
                            'source_type' => $this->mapTierToSourceType($queryInfo['tier'], $item['link']),
                        ];
                    }
                }
            }

            if (empty($allEvidence)) {
                $currentRetry++;
                continue;
            }

            $this->updateProgress($alumni->nim, 60, "Fase A: Menganalisis hasil pencarian dengan Gemini AI...");
            $analysis = $this->geminiAnalysis->analyze($allEvidence, $alumniData);

            // Post-validation: cek UMM affiliation check
            if (isset($analysis['is_umm_verified']) && $analysis['is_umm_verified'] === false) {
                $analysis['confidence'] = min($analysis['confidence'], 0.30);
                Log::warning("TrackingService: UMM affiliation NOT verified for {$alumni->nim}. Capping confidence.");
            }

            // Post-validation: URL LinkedIn valid format profil
            if (!empty($analysis['linkedin_url'])) {
                if (!preg_match('#^https?://([a-z]{2,3}\.)?linkedin\.com/.*$#', $analysis['linkedin_url'])) {
                    $analysis['linkedin_url'] = null;
                }
            }

            if ($bestAnalysis === null || $analysis['confidence'] > $bestAnalysis['confidence']) {
                $bestAnalysis = $analysis;
                $allEvidenceFinal = $allEvidence;
            }

            if ($bestAnalysis['confidence'] >= 0.70) {
                break;
            }

            Log::info("TrackingService: Confidence {$analysis['confidence']} < 0.70. Retrying...");
            $currentRetry++;
            sleep(3);
        }

        if ($bestAnalysis === null) {
            $alumni->update(['tracking_status' => 'not_found', 'last_tracked_at' => now()]);
            return ['status' => 'not_found', 'message' => 'Tidak ditemukan evidence di web.'];
        }

        foreach ($allEvidenceFinal as $evidence) {
            EvidenceLog::create([
                'alumni_nim'  => $alumni->nim,
                'source_type' => $evidence['source_type'],
                'source_url'  => $evidence['source_url'],
                'raw_snippet' => substr($evidence['raw_snippet'], 0, 1000),
                'searched_at' => now(),
            ]);
        }

        // FASE B: Social Media Discovery
        $sosmedAlumni = [
            'ig_url'     => null,
            'fb_url'     => null,
            'tiktok_url' => null
        ];

        if ($bestAnalysis['confidence'] >= 0.50) {
            $this->updateProgress($alumni->nim, 75, "Fase B: Penemuan media sosial berbasis identitas...");
            
            // Extract username if possible
            $linkedinUsername = null;
            if (!empty($bestAnalysis['linkedin_url'])) {
                $parts = explode('/in/', $bestAnalysis['linkedin_url']);
                if (count($parts) > 1) {
                    $linkedinUsername = trim(explode('?', $parts[1])[0], '/');
                }
            }

            $sosmedQueries = $this->queryGenerator->generateSocialMediaQueries(
                $alumni->nama_lengkap, 
                $linkedinUsername, 
                $bestAnalysis['instansi'], 
                $bestAnalysis['lokasi']
            );

            if (!empty($sosmedQueries)) {
                $sosmedQueryStrings = array_unique(array_column($sosmedQueries, 'query'));
                $sosmedResults = $this->serperSearch->searchConcurrent($sosmedQueryStrings, 3);
                
                $sosmedEvidence = [];
                foreach ($sosmedQueries as $queryInfo) {
                    $qStr = $queryInfo['query'];
                    SearchQuery::create([
                        'alumni_nim'    => $alumni->nim,
                        'query_text'    => $qStr,
                        'search_tier'   => 'tier2_ig_tiktok',
                        'results_count' => $sosmedResults[$qStr]['totalResults'] ?? 0,
                        'searched_at'   => now(),
                    ]);
                    
                    foreach ($sosmedResults[$qStr]['items'] ?? [] as $item) {
                        $sosmedEvidence[] = [
                            'source_url'  => $item['link'],
                            'raw_snippet' => $item['snippet'] . ' - ' . $item['title'],
                            'source_type' => $this->mapTierToSourceType('tier2_ig_tiktok', $item['link']),
                        ];
                    }
                }

                if (!empty($sosmedEvidence)) {
                    $sosmedAlumni = $this->geminiAnalysis->analyzeSocialMedia($sosmedEvidence, [
                        'nama' => $alumni->nama_lengkap,
                        'instansi' => $bestAnalysis['instansi'],
                        'lokasi' => $bestAnalysis['lokasi']
                    ]);
                }
            }
        }

        // Secondary Enrichment (Instansi)
        $instansi = $bestAnalysis['instansi'];
        $sosmedInstansi = [
            'linkedin' => null,
            'ig' => null,
            'fb' => null,
            'tiktok' => null
        ];

        if (!empty($instansi) && $instansi !== 'null') {
            $this->updateProgress($alumni->nim, 85, "Secondary enrichment untuk perusahaan: {$instansi}...");
            $enrichQuery = "\"{$instansi}\" site:linkedin.com/company OR site:instagram.com OR site:facebook.com OR site:tiktok.com";
            $enrichResult = $this->serperSearch->searchConcurrent([$enrichQuery], 4);
            $items = $enrichResult[$enrichQuery]['items'] ?? [];
            
            foreach ($items as $item) {
                $url = collect(explode('?', $item['link']))->first(); // clean params
                if (empty($sosmedInstansi['linkedin']) && str_contains($url, 'linkedin.com/company/')) {
                    $sosmedInstansi['linkedin'] = $url;
                } elseif (empty($sosmedInstansi['ig']) && str_contains($url, 'instagram.com/')) {
                    $sosmedInstansi['ig'] = $url;
                } elseif (empty($sosmedInstansi['fb']) && str_contains($url, 'facebook.com/')) {
                    $sosmedInstansi['fb'] = $url;
                } elseif (empty($sosmedInstansi['tiktok']) && str_contains($url, 'tiktok.com/')) {
                    $sosmedInstansi['tiktok'] = $url;
                }
            }
        }

        $confidence = $bestAnalysis['confidence'];
        $autoVerifyThreshold = config('scoutalumni.tracking.auto_verify_threshold', 0.8);
        $needsAuditThreshold = config('scoutalumni.tracking.needs_audit_threshold', 0.5);

        if ($confidence >= $autoVerifyThreshold) {
            $newStatus = 'auto_verified';
        } elseif ($confidence >= $needsAuditThreshold) {
            $newStatus = 'needs_audit';
        } else {
            $newStatus = 'not_found';
        }

        // Save tracking result
        $result = TrackingResult::updateOrCreate(
            ['alumni_nim' => $alumni->nim],
            [
                'jabatan'          => $bestAnalysis['jabatan'],
                'instansi'         => $bestAnalysis['instansi'],
                'kategori_pekerjaan'=> $bestAnalysis['kategori_pekerjaan'],
                'tipe_posisi'      => $bestAnalysis['tipe_posisi'] ?? null,
                'posisi_sejak'     => $bestAnalysis['posisi_sejak'] ?? null,
                'lokasi'           => $bestAnalysis['lokasi'],
                'linkedin_url'     => $bestAnalysis['linkedin_url'],
                'ig_url'           => $sosmedAlumni['ig_url'] ?? null,
                'fb_url'           => $sosmedAlumni['fb_url'] ?? null,
                'tiktok_url'       => $sosmedAlumni['tiktok_url'] ?? null,
                'email'            => $bestAnalysis['email'],
                'no_hp'            => $bestAnalysis['no_hp'],
                'is_umm_verified'  => $bestAnalysis['is_umm_verified'] ?? false,
                'umm_evidence'     => $bestAnalysis['umm_evidence'] ?? null,
                'sosmed_instansi_linkedin' => $sosmedInstansi['linkedin'],
                'sosmed_instansi_ig'       => $sosmedInstansi['ig'],
                'sosmed_instansi_fb'       => $sosmedInstansi['fb'],
                'sosmed_instansi_tiktok'   => $sosmedInstansi['tiktok'],
                'confidence_score' => $confidence,
                'ai_notes'         => trim(($bestAnalysis['notes'] ?? '') . "\n" . ($bestAnalysis['catatan_posisi'] ?? '')),
                'source_type'      => 'serper_gemini',
            ]
        );
        $result->touch();

        TrackingHistory::create([
            'alumni_nim'    => $alumni->nim,
            'snapshot_data' => array_merge($bestAnalysis, ['sosmed_instansi' => $sosmedInstansi]),
            'changed_reason' => 'Tracking otomatis - confidence: ' . round($confidence * 100) . '%',
            'created_at'    => now(),
        ]);

        $alumni->update([
            'tracking_status' => $newStatus,
            'last_tracked_at' => now(),
        ]);

        $this->updateProgress($alumni->nim, 100, "Selesai! Status: {$newStatus}");
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

    /**
     * Check if a snippet/title contains contextual keywords related to the alumni.
     * Keywords are dynamically generated by Gemini AI.
     */
    private function containsContextualKeywords(string $text, array $keywords): bool
    {
        $text = strtolower($text);
        
        foreach ($keywords as $keyword) {
            $keyword = strtolower(trim($keyword));
            if ($keyword && str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update tracking progress in both Cache (for polling) and Events (for broadcasting).
     */
    protected function updateProgress(string $nim, int $progress, string $message): void
    {
        $data = [
            'progress' => $progress,
            'message'  => $message,
            'updated_at' => now()->toDateTimeString(),
        ];

        // Store in cache for 10 minutes
        Cache::put("tracking_progress_{$nim}", $data, 600);

        // Dispatch broadcast event
        TrackingProgressUpdated::dispatch($nim, $progress, $message);
    }
}
