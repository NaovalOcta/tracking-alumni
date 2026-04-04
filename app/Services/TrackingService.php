<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\ConflictLog;
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
    protected FetchCacheService $fetchCache;
    protected IdentityValidator $identityValidator;
    protected PriorityResolverEngine $priorityResolver;

    /**
     * Minimum evidence count from Tier 1 (LinkedIn) to skip remaining tiers.
     */
    protected int $earlyStopThreshold = 3;

    public function __construct(
        QueryGeneratorService $queryGenerator,
        SerperSearchService $serperSearch,
        GeminiAnalysisService $geminiAnalysis,
        FetchCacheService $fetchCache,
        IdentityValidator $identityValidator,
        PriorityResolverEngine $priorityResolver,
    ) {
        $this->queryGenerator = $queryGenerator;
        $this->serperSearch = $serperSearch;
        $this->geminiAnalysis = $geminiAnalysis;
        $this->fetchCache = $fetchCache;
        $this->identityValidator = $identityValidator;
        $this->priorityResolver = $priorityResolver;
    }

    /**
     * Track a single alumni: V7.2 17-step pipeline.
     *
     * PIPELINE STEPS:
     *  1. Mark alumni as sedang_dilacak
     *  2. Generate queries via QueryGeneratorService
     *  3. Query deduplication via FetchCacheService
     *  4. Serper search + URL caching (handled inside SerperSearchService)
     *  5. URL deduplication (existing)
     *  6. Identity Gate (IdentityValidator)
     *  7. V7.2 Gemini analysis (analyzeV72)
     *  8. Priority Resolution (PriorityResolverEngine)
     *  9. Mandatory Context Constraint (social URL nullification)
     * 10. Social Media Discovery (Phase B) with Mandatory Context Constraint
     * 11. Secondary Enrichment (instansi social media)
     * 12. Coverage Details calculation
     * 13. Final Reliability determination
     * 14. Decision Trace assembly
     * 15. Persist results
     * 16. Status determination
     * 17. Ambiguity Detection
     *
     * @return array{status: string, message: string}
     */
    public function trackAlumni(Alumni $alumni): array
    {
        Log::info("TrackingService: Starting V7.2 tracking for {$alumni->nim} - {$alumni->nama_lengkap}");

        // ============================================================
        // STEP 1: Mark alumni as in-progress (EXISTING — PRESERVED)
        // ============================================================
        $alumni->update(['tracking_status' => 'sedang_dilacak']);

        $alumniData = [
            'nim'           => $alumni->nim,
            'nama_lengkap'  => $alumni->nama_lengkap,
            'nama_variasi'  => $alumni->nama_variasi ?? [],
            'prodi'         => $alumni->prodi,
            'tahun_lulus'   => $alumni->tahun_lulus,
        ];

        $decisionTrace = [
            'accepted_signals' => [],
            'rejected_signals' => [],
            'ignored_signals'  => [],
        ];

        $this->updateProgress($alumni->nim, 15, "Menyiapkan 12 query OSINT kombinatorial...");

        // ============================================================
        // STEP 2: Generate Exhaustive Queries (V7.2 NEW)
        // ============================================================
        $queries = $this->queryGenerator->generateExhaustiveQueries($alumni);

        // ============================================================
        // STEP 3: Query Deduplication via FetchCacheService
        // ============================================================
        $filteredQueries = [];
        foreach ($queries as $queryInfo) {
            $qStr = $queryInfo['query'];
            if ($this->fetchCache->isQueryDuplicate($qStr)) {
                Log::info("TrackingService: Query deduplicated (already run today): {$qStr}");
                $decisionTrace['ignored_signals'][] = "Query deduplicated: '{$qStr}' (same-day duplicate)";
                continue;
            }
            $filteredQueries[] = $queryInfo;
        }

        if (empty($filteredQueries)) {
            Log::info("TrackingService: All 12 exhaustive queries deduplicated. Likely already tracked today.");
            // However, we proceed to check if there's any existing evidence or let it fail gracefully
        }

        $queryStrings = array_column($filteredQueries, 'query');
        $this->updateProgress($alumni->nim, 30, "Mengeksekusi 12 query paralel (3 batch)...");

        // ============================================================
        // STEP 4: Serper Search + URL Caching (Exhaustive)
        // ============================================================
        $concurrentResults = $this->serperSearch->searchConcurrent($queryStrings, 5); // Increased max results to 5 for exhaustive search

        // ============================================================
        // STEP 5: Evidence Aggregation & Snippet Deduplication
        // ============================================================
        $allEvidenceFinal = [];
        $uniqueSnippets = [];

        foreach ($queries as $queryInfo) {
            $qStr = $queryInfo['query'];
            $searchResults = $concurrentResults[$qStr] ?? ['items' => [], 'totalResults' => 0];

            // Record execution in DB
            SearchQuery::create([
                'alumni_nim'    => $alumni->nim,
                'query_text'    => $qStr,
                'search_tier'   => $queryInfo['tier'],
                'results_count' => $searchResults['totalResults'],
                'searched_at'   => now(),
            ]);

            foreach ($searchResults['items'] as $item) {
                // Deduplicate by URL AND by snippet (to save Gemini tokens)
                $snippetHash = md5($item['snippet'] . $item['title']);
                
                if (!collect($allEvidenceFinal)->contains('source_url', $item['link']) && !in_array($snippetHash, $uniqueSnippets)) {
                    // V7.2.2: Apply Smart Pre-Filter
                    if (!$this->isSnippetRelevant($item['snippet'] . ' ' . $item['title'], $item['link'], $alumniData)) {
                        continue;
                    }

                    $allEvidenceFinal[] = [
                        'source_url'  => $item['link'],
                        'raw_snippet' => $item['snippet'] . ' - ' . $item['title'],
                        'source_type' => $this->mapTierToSourceType($queryInfo['tier'], $item['link']),
                    ];
                    $uniqueSnippets[] = $snippetHash;
                }
            }
        }

        // No evidence found
        if (empty($allEvidenceFinal)) {
            Log::warning("TrackingService: Exhaustive search found 0 evidence for {$alumni->nim}");
            $alumni->update(['tracking_status' => 'not_found', 'last_tracked_at' => now()]);
            return ['status' => 'not_found', 'message' => 'Tidak ditemukan evidence di web setelah pencarian mendalam.'];
        }

        Log::info("TrackingService: Exhaustive search completed for {$alumni->nim}. Total unique evidence: " . count($allEvidenceFinal));

        // ============================================================
        // STEP 6: Identity Gate (NEW — IdentityValidator)
        // V7.2 §B.1, §C.1, §D.1
        // ============================================================
        $this->updateProgress($alumni->nim, 45, "V7.2: Validasi identitas alumni...");

        // Prepare evidence format for IdentityValidator (expects 'url', 'title', 'snippet')
        $identityEvidence = array_map(function ($ev) {
            return [
                'url'     => $ev['source_url'],
                'title'   => '',
                'snippet' => $ev['raw_snippet'],
            ];
        }, $allEvidenceFinal);

        $identityResult = $this->identityValidator->validate($alumniData, $identityEvidence);
        $identityConfidence = $identityResult['identity_confidence'];
        $gateStatus = $identityResult['gate_status'];

        Log::info("TrackingService: Identity gate result for {$alumni->nim}: score={$identityConfidence}, gate={$gateStatus}");

        $rejectThreshold = config('scoutalumni.identity_gate.reject_threshold', 0.70);
        $strongThreshold = config('scoutalumni.identity_gate.strong_threshold', 0.85);

        // STEP 6a: Identity gate SHORT-CIRCUIT for REJECT (<0.70)
        if ($identityConfidence < $rejectThreshold) {
            Log::warning("TrackingService: Identity REJECTED for {$alumni->nim}. Score: {$identityConfidence}. No Gemini call.");

            $decisionTrace['rejected_signals'][] = "Identity gate REJECTED: score {$identityConfidence} < {$rejectThreshold}. Pipeline aborted.";

            // Save result with rejection metadata
            TrackingResult::updateOrCreate(
                ['alumni_nim' => $alumni->nim],
                [
                    'identity_confidence' => $identityConfidence,
                    'confidence_score'    => 0.0,
                    'decision_trace'      => $decisionTrace,
                    'ai_notes'            => "Identity gate rejected. Score: {$identityConfidence}. Signals: " . json_encode($identityResult['signals']),
                    'source_type'         => 'serper_gemini',
                ]
            );

            // Save evidence logs even on rejection
            foreach ($allEvidenceFinal as $evidence) {
                EvidenceLog::create([
                    'alumni_nim'  => $alumni->nim,
                    'source_type' => $evidence['source_type'],
                    'source_url'  => $evidence['source_url'],
                    'raw_snippet' => substr($evidence['raw_snippet'], 0, 1000),
                    'searched_at' => now(),
                ]);
            }

            $alumni->update(['tracking_status' => 'not_found', 'last_tracked_at' => now()]);

            $this->updateProgress($alumni->nim, 100, "Identity gate: REJECTED (score {$identityConfidence})");

            return [
                'status'     => 'not_found',
                'message'    => "Identity gate REJECTED. Score: {$identityConfidence}",
                'confidence' => 0.0,
            ];
        }

        // STEP 6b: Determine reliability cap based on gate status
        $reliabilityCap = null; // No cap for STRONG
        if ($identityConfidence < $strongThreshold) {
            $reliabilityCap = 'medium'; // WEAK identity → cap at medium
            $decisionTrace['accepted_signals'][] = "Identity gate WEAK: score {$identityConfidence}. Reliability capped at 'medium'.";
        } else {
            $decisionTrace['accepted_signals'][] = "Identity gate STRONG: score {$identityConfidence}. No reliability cap.";
        }

        // ============================================================
        // STEP 7: V7.2 Gemini Analysis (MODIFIED — uses analyzeV72)
        // ============================================================
        $this->updateProgress($alumni->nim, 55, "V7.2: Analisis Gemini Zero-Tolerance...");

        $geminiResult = $this->geminiAnalysis->analyzeV72($allEvidenceFinal, $alumniData);

        $extractedData = $geminiResult['extracted_data'] ?? [];
        $socialSignals = $geminiResult['social_signals'] ?? [];
        $extractedConflicts = $geminiResult['extracted_conflicts'] ?? [];

        Log::info("TrackingService: Gemini V7.2 result for {$alumni->nim}", [
            'company'   => $extractedData['company'] ?? null,
            'position'  => $extractedData['position'] ?? null,
            'conflicts' => count($extractedConflicts),
        ]);

        // ============================================================
        // STEP 8: Priority Resolution (NEW — PriorityResolverEngine)
        // V7.2 §B.2, §C.3
        // ============================================================
        $this->updateProgress($alumni->nim, 65, "V7.2: Menyelesaikan konflik data via hierarki...");

        $resolverResult = $this->priorityResolver->resolve($geminiResult, $allEvidenceFinal);
        $resolvedData = $resolverResult['resolved_data'] ?? $extractedData;
        $conflictTrace = $resolverResult['conflict_trace'] ?? [];
        $conflictPenalty = $resolverResult['penalties_applied'] ?? 0.0;

        // Persist each conflict into conflict_logs table
        foreach ($conflictTrace as $conflict) {
            try {
                ConflictLog::create([
                    'alumni_nim'        => $alumni->nim,
                    'field_name'        => $conflict['field'],
                    'rejected_values'   => $conflict['rejected_values'] ?? [],
                    'resolution_reason' => $conflict['resolution_reason'] ?? '',
                    'created_at'        => now(),
                ]);
            } catch (\Exception $e) {
                Log::warning("TrackingService: Failed to create ConflictLog for {$alumni->nim}", [
                    'field' => $conflict['field'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($conflictPenalty > 0) {
            $decisionTrace['accepted_signals'][] = "Conflict penalty applied: -{$conflictPenalty} (from " . count($conflictTrace) . " conflicts)";
        }

        // ============================================================
        // STEP 9: Mandatory Context Constraint §C.2 (NEW)
        // Social URL nullification if no company mention AND no LinkedIn link
        // ============================================================
        $platforms = ['instagram', 'facebook', 'tiktok'];
        foreach ($platforms as $platform) {
            $signal = $socialSignals[$platform] ?? null;
            if ($signal && !empty($signal['url'])) {
                $hasCompanyMention = (bool) ($signal['has_company_mention'] ?? false);
                $hasLinkedinLink = (bool) ($signal['has_linkedin_link'] ?? false);

                if (!$hasCompanyMention && !$hasLinkedinLink) {
                    Log::info("TrackingService: Mandatory Context Constraint — {$platform} URL nullified for {$alumni->nim}");
                    $socialSignals[$platform]['url'] = null;
                    $decisionTrace['rejected_signals'][] = "Mandatory Context Constraint: {$platform} URL rejected — no company mention AND no LinkedIn link in profile";
                } else {
                    $decisionTrace['accepted_signals'][] = "{$platform} URL accepted — " .
                        ($hasCompanyMention ? "has company mention" : "") .
                        ($hasCompanyMention && $hasLinkedinLink ? " + " : "") .
                        ($hasLinkedinLink ? "has LinkedIn link" : "");
                }
            }
        }

        // ============================================================
        // STEP 10: Social Media Discovery — Phase B (EXISTING — MODIFIED)
        // Gated on confidence >= 0.50, applies Mandatory Context Constraint
        // ============================================================
        $sosmedAlumni = [
            'ig_url'     => $socialSignals['instagram']['url'] ?? null,
            'fb_url'     => $socialSignals['facebook']['url'] ?? null,
            'tiktok_url' => $socialSignals['tiktok']['url'] ?? null,
        ];

        // Use resolved data for social discovery context
        $instansi = $resolvedData['company'] ?? null;
        $lokasi = $extractedData['location'] ?? null;

        // Build a preliminary confidence for Phase B gating
        // Use 0.60 as base since we passed identity gate
        $preliminaryConfidence = 0.60;

        if ($preliminaryConfidence >= 0.50) {
            $this->updateProgress($alumni->nim, 75, "Fase B: Penemuan media sosial berbasis identitas...");

            // Extract LinkedIn URL from resolved/extracted data
            $linkedinUrl = $this->cleanProfileUrl($resolvedData['linkedin_url'] ?? $extractedData['linkedin_url'] ?? null, 'linkedin');

            $linkedinUsername = null;
            if (!empty($linkedinUrl)) {
                $parts = explode('/in/', $linkedinUrl);
                if (count($parts) > 1) {
                    $linkedinUsername = trim(explode('?', $parts[1])[0], '/');
                }
            }

            $sosmedQueries = $this->queryGenerator->generateSocialMediaQueries(
                $alumni->nama_lengkap,
                $linkedinUsername,
                $instansi,
                $lokasi
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
                    $sosmedAnalysis = $this->geminiAnalysis->analyzeSocialMedia($sosmedEvidence, [
                        'nama' => $alumni->nama_lengkap,
                        'instansi' => $instansi,
                        'lokasi' => $lokasi,
                    ]);

                    // Apply Mandatory Context Constraint to Phase B social results
                    // If analyzeSocialMedia returned URLs, they must pass context check
                    // (analyzeSocialMedia already applies multi-signal matching, so we accept its results)
                    if (!empty($sosmedAnalysis['ig_url'])) {
                        $sosmedAlumni['ig_url'] = $sosmedAlumni['ig_url'] ?? $sosmedAnalysis['ig_url'];
                    }
                    if (!empty($sosmedAnalysis['fb_url'])) {
                        $sosmedAlumni['fb_url'] = $sosmedAlumni['fb_url'] ?? $sosmedAnalysis['fb_url'];
                    }
                    if (!empty($sosmedAnalysis['tiktok_url'])) {
                        $sosmedAlumni['tiktok_url'] = $sosmedAlumni['tiktok_url'] ?? $sosmedAnalysis['tiktok_url'];
                    }
                }
            }
        }

        // ============================================================
        // STEP 11: Secondary Enrichment (Instansi) (EXISTING — PRESERVED)
        // ============================================================
        $sosmedInstansi = [
            'linkedin' => null,
            'ig' => null,
            'fb' => null,
            'tiktok' => null,
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

        // ============================================================
        // STEP 12: Coverage Details + Coverage Tier (NEW)
        // V7.2 §E coverage_detail
        // ============================================================
        $linkedinUrl = $this->cleanProfileUrl($resolvedData['linkedin_url'] ?? $extractedData['linkedin_url'] ?? null, 'linkedin');
        $sosmedAlumni['ig_url'] = $this->cleanProfileUrl($sosmedAlumni['ig_url'] ?? null, 'instagram');
        $sosmedAlumni['fb_url'] = $this->cleanProfileUrl($sosmedAlumni['fb_url'] ?? null, 'facebook');
        $sosmedAlumni['tiktok_url'] = $this->cleanProfileUrl($sosmedAlumni['tiktok_url'] ?? null, 'tiktok');

        $coverageDetails = [
            'linkedin'  => !empty($linkedinUrl),
            'company'   => !empty($resolvedData['company'] ?? null),
            'position'  => !empty($resolvedData['position'] ?? null),
            'instagram' => !empty($sosmedAlumni['ig_url']),
            'email'     => !empty($resolvedData['email'] ?? null),
        ];

        $fieldsCovered = count(array_filter($coverageDetails));

        if ($fieldsCovered >= 4) {
            $coverageTier = 'high';
        } elseif ($fieldsCovered >= 3) {
            $coverageTier = 'medium';
        } else {
            $coverageTier = 'low';
        }

        // ============================================================
        // STEP 13: Final Reliability Determination (NEW)
        // ============================================================

        // Start with base confidence from coverage
        $confidence = $this->calculateBaseConfidence($resolvedData, $coverageDetails, $identityConfidence);

        // 13a: Apply UMM verification penalty (EXISTING logic)
        $isUmmAlumni = $resolvedData['is_umm_alumni'] ?? $extractedData['is_umm_alumni'] ?? 'unknown';
        if ($isUmmAlumni === 'false' || $isUmmAlumni === false) {
            $confidence = min($confidence, 0.30);
            Log::warning("TrackingService: UMM affiliation NOT verified for {$alumni->nim}. Capping confidence.");
            $decisionTrace['rejected_signals'][] = "UMM affiliation NOT verified — confidence capped at 0.30";
        }

        // 13b: Apply conflict penalties from STEP 8
        $confidence = max(0.0, $confidence - $conflictPenalty);

        // 13c: Apply identity gate cap (WEAK → max 'medium')
        $reliability = 'low';
        if ($confidence >= 0.80) {
            $reliability = 'high';
        } elseif ($confidence >= 0.50) {
            $reliability = 'medium';
        }

        // Apply WEAK identity cap: force reliability to max 'medium'
        if ($reliabilityCap === 'medium' && $reliability === 'high') {
            $reliability = 'medium';
            $decisionTrace['accepted_signals'][] = "Reliability downgraded from 'high' to 'medium' due to WEAK identity gate";
        }

        // ============================================================
        // STEP 14: Decision Trace Assembly (NEW — §E)
        // ============================================================
        // Decision trace has been built incrementally throughout the pipeline.
        // Final assembly with metadata:
        $decisionTrace['identity_gate'] = [
            'score'  => $identityConfidence,
            'status' => $gateStatus,
            'signals' => $identityResult['signals'],
        ];
        $decisionTrace['coverage'] = [
            'tier'    => $coverageTier,
            'details' => $coverageDetails,
            'fields_covered' => $fieldsCovered,
        ];
        $decisionTrace['reliability'] = $reliability;
        $decisionTrace['conflict_penalty'] = $conflictPenalty;

        // ============================================================
        // STEP 15: Persist Results (MODIFIED — includes V7.2 fields)
        // ============================================================
        $this->updateProgress($alumni->nim, 90, "Menyimpan hasil tracking...");

        // Save evidence logs
        foreach ($allEvidenceFinal as $evidence) {
            EvidenceLog::create([
                'alumni_nim'  => $alumni->nim,
                'source_type' => $evidence['source_type'],
                'source_url'  => $evidence['source_url'],
                'raw_snippet' => substr($evidence['raw_snippet'], 0, 1000),
                'searched_at' => now(),
            ]);
        }

        // Map resolved/extracted data to TrackingResult fields
        $result = TrackingResult::updateOrCreate(
            ['alumni_nim' => $alumni->nim],
            [
                'jabatan'          => $resolvedData['position'] ?? null,
                'instansi'         => $resolvedData['company'] ?? null,
                'kategori_pekerjaan'=> $extractedData['kategori_pekerjaan'] ?? null,
                'tipe_posisi'      => $extractedData['tipe_posisi'] ?? null,
                'posisi_sejak'     => $extractedData['posisi_sejak'] ?? null,
                'lokasi'           => $extractedData['location'] ?? null,
                'linkedin_url'     => $linkedinUrl,
                'ig_url'           => $sosmedAlumni['ig_url'] ?? null,
                'fb_url'           => $sosmedAlumni['fb_url'] ?? null,
                'tiktok_url'       => $sosmedAlumni['tiktok_url'] ?? null,
                'email'            => $resolvedData['email'] ?? null,
                'no_hp'            => $resolvedData['phone'] ?? null,
                'is_umm_verified'  => ($isUmmAlumni === 'true' || $isUmmAlumni === true),
                'umm_evidence'     => $extractedData['umm_evidence'] ?? null,
                'sosmed_instansi_linkedin' => $sosmedInstansi['linkedin'],
                'sosmed_instansi_ig'       => $sosmedInstansi['ig'],
                'sosmed_instansi_fb'       => $sosmedInstansi['fb'],
                'sosmed_instansi_tiktok'   => $sosmedInstansi['tiktok'],
                'confidence_score'    => $confidence,
                'ai_notes'            => $geminiResult['alasan_analisis'] ?? 'Tidak ada catatan analisis.',
                'source_type'         => 'serper_gemini',
                // V7.2 new fields
                'identity_confidence' => $identityConfidence,
                'coverage_tier'       => $coverageTier,
                'coverage_details'    => $coverageDetails,
                'decision_trace'      => $decisionTrace,
                'conflict_trace'      => $conflictTrace,
            ]
        );
        $result->touch();

        // Save tracking history snapshot
        TrackingHistory::create([
            'alumni_nim'     => $alumni->nim,
            'snapshot_data'  => array_merge(
                $resolvedData,
                ['sosmed_instansi' => $sosmedInstansi],
                ['identity_confidence' => $identityConfidence],
                ['coverage_tier' => $coverageTier],
                ['coverage_details' => $coverageDetails],
                ['conflict_trace' => $conflictTrace],
            ),
            'changed_reason' => 'V7.2 Tracking otomatis - confidence: ' . round($confidence * 100) . '% | Identity: ' . $gateStatus,
            'created_at'     => now(),
        ]);

        // ============================================================
        // STEP 16: Status Determination (EXISTING — MODIFIED with V7.2 reliability caps)
        // ============================================================
        $autoVerifyThreshold = config('scoutalumni.tracking.auto_verify_threshold', 0.8);
        $needsAuditThreshold = config('scoutalumni.tracking.needs_audit_threshold', 0.5);

        if ($confidence >= $autoVerifyThreshold) {
            $newStatus = 'auto_verified';
        } elseif ($confidence >= $needsAuditThreshold) {
            $newStatus = 'needs_audit';
        } else {
            $newStatus = 'not_found';
        }

        // Apply reliability cap to status
        if ($reliabilityCap === 'medium' && $newStatus === 'auto_verified') {
            $newStatus = 'needs_audit';
            $decisionTrace['accepted_signals'][] = "Status downgraded from 'auto_verified' to 'needs_audit' due to WEAK identity gate";

            // Update decision trace in result
            $result->update(['decision_trace' => $decisionTrace]);
        }

        // ============================================================
        // STEP 17: Ambiguity Detection §D.2 (NEW)
        // ============================================================
        $ambiguousIdentity = false;
        if (!empty($extractedConflicts)) {
            // Check for multiple valid candidates: same name, different companies, both high-rank sources
            $companyConflicts = collect($extractedConflicts)->filter(function ($conflict) {
                return ($conflict['field'] ?? '') === 'company';
            });

            if ($companyConflicts->count() > 0) {
                // Check if conflicting sources are both high-rank (rank <= 3)
                foreach ($companyConflicts as $conflict) {
                    $conflictUrl = $conflict['conflicting_source_url'] ?? $conflict['source_url'] ?? '';
                    $primaryUrl = $conflict['primary_source_url'] ?? '';

                    if (!empty($conflictUrl)) {
                        $conflictType = $this->priorityResolver->classifySource($conflictUrl);
                        $conflictRank = PriorityResolverEngine::HIERARCHY[$conflictType] ?? 4;

                        $primaryRank = 4;
                        if (!empty($primaryUrl)) {
                            $primaryType = $this->priorityResolver->classifySource($primaryUrl);
                            $primaryRank = PriorityResolverEngine::HIERARCHY[$primaryType] ?? 4;
                        }

                        // Both high-rank sources (rank <= 3) with different companies → ambiguous
                        if ($conflictRank <= 3 && $primaryRank <= 3) {
                            $ambiguousIdentity = true;
                            break;
                        }
                    }
                }
            }
        }

        if ($ambiguousIdentity) {
            $newStatus = 'needs_audit';
            $decisionTrace['ambiguous_identity'] = true;
            $decisionTrace['rejected_signals'][] = "Ambiguous identity detected: multiple high-rank sources with conflicting company data. Manual verification required.";

            Log::warning("TrackingService: Ambiguous identity for {$alumni->nim}. Requires manual verification.");

            // Update result with ambiguity flag
            $result->update([
                'decision_trace' => $decisionTrace,
                'manual_confidence' => null, // Requires admin input
            ]);
        }

        // Final status update
        $alumni->update([
            'tracking_status' => $newStatus,
            'last_tracked_at' => now(),
        ]);

        $this->updateProgress($alumni->nim, 100, "Selesai! Status: {$newStatus}");
        Log::info("TrackingService: Completed {$alumni->nim} — status: {$newStatus}, confidence: {$confidence}, identity: {$identityConfidence}, coverage: {$coverageTier}");

        return [
            'status'     => $newStatus,
            'message'    => "Tracking selesai. Status: {$newStatus}, Confidence: " . round($confidence * 100) . '%',
            'confidence' => $confidence,
        ];
    }

    /**
     * Calculate base confidence from resolved data and coverage.
     */
    protected function calculateBaseConfidence(array $resolvedData, array $coverageDetails, float $identityConfidence): float
    {
        $fieldsCovered = count(array_filter($coverageDetails));

        // Base confidence from field coverage
        $coverageScore = $fieldsCovered / 5.0; // 5 key fields

        // Weighted combination: identity confidence + coverage
        $confidence = (0.6 * $identityConfidence) + (0.4 * $coverageScore);

        // Boost if LinkedIn found (strong signal)
        if ($coverageDetails['linkedin'] ?? false) {
            $confidence = min(1.0, $confidence + 0.10);
        }

        // Boost if company found
        if ($coverageDetails['company'] ?? false) {
            $confidence = min(1.0, $confidence + 0.05);
        }

        return round(min(1.0, $confidence), 4);
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
     * Smart Tiered Pre-Filter (V7.2.2)
     * Handles context-aware filtering safely but strictly to reduce noise.
     */
    private function isSnippetRelevant(string $snippetText, string $url, array $alumniData): bool
    {
        $text = strtolower($snippetText);
        $url = strtolower($url);

        // Rule 1: Allow LinkedIn unconditionally (due to snippet truncation)
        if (str_contains($url, 'linkedin.com/in/')) return true;

        // Rule 2: Name must exist
        $nameParts = explode(' ', strtolower($alumniData['nama_lengkap']));
        $nameMatch = false;
        foreach ($nameParts as $part) {
            if (strlen($part) > 2 && str_contains($text, $part)) {
                $nameMatch = true;
                break;
            }
        }
        if (!$nameMatch) return false;

        // Rule 3: Strict Context for General Web & Other Social Media
        return preg_match('/\bumm\b/', $text) || (str_contains($text, 'muhammadiyah') && str_contains($text, 'malang')) || str_contains($text, strtolower($alumniData['prodi']));
    }

    private function cleanProfileUrl(?string $url, string $platform): ?string {
        if (empty($url)) return null;
        $url = strtok($url, '?'); // Remove query params
        
        return match($platform) {
            'linkedin' => preg_match('#^https?://([a-z]{2,3}\.)?linkedin\.com/in/[^/]+/?$#i', $url) ? $url : null,
            'instagram' => preg_match('#^https?://(www\.)?instagram\.com/(?!p/|reel/|explore/)[^/]+/?$#i', $url) ? $url : null,
            'facebook' => preg_match('#^https?://(www\.)?facebook\.com/(?!story\.php|photo\.php|groups/)[^/]+/?$#i', $url) ? $url : null,
            'tiktok' => preg_match('#^https?://(www\.)?tiktok\.com/@[^/]+/?$#i', $url) ? $url : null,
            default => $url
        };
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
