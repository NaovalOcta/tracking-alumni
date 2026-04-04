<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAnalysisService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('scoutalumni.gemini.api_key', '');
        $this->model = config('scoutalumni.gemini.model', 'gemini-2.0-flash');
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Generate a tracking strategy for a specific alumni.
     * Includes optimized search queries and contextual keywords for early-stop logic.
     *
     * @param  Alumni  $alumni
     * @return array{queries: array<int, array{query: string, tier: string}>, context_keywords: string[]}
     */
    public function generateTrackingStrategy($alumni): array
    {
        $defaultResult = [
            'queries' => [], // Will be filled by QueryGenerator fallback if this fails
            'context_keywords' => [
                strtolower($alumni->prodi),
                strtolower($alumni->fakultas),
            ],
        ];

        if (!$this->isConfigured()) {
            return $defaultResult;
        }

        $prompt = $this->buildStrategyPrompt($alumni);

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(20)->post($url, [
                'contents' => [
                    [
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsed = json_decode($text, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                    $queries = $parsed['queries'] ?? [];
                    
                    // Sanitize queries: Ensure 'tier' is valid for database ENUM
                    $validTiers = ['tier1_linkedin', 'tier2_scholar_github', 'tier3_news_web'];
                    $sanitizedQueries = array_map(function($q) use ($validTiers) {
                        $tier = $q['tier'] ?? 'tier3_news_web';
                        
                        // Mapping common AI deviations
                        if (!in_array($tier, $validTiers)) {
                            if (str_contains($tier, 'linkedin')) $tier = 'tier1_linkedin';
                            elseif (str_contains($tier, 'scholar') || str_contains($tier, 'github')) $tier = 'tier2_scholar_github';
                            else $tier = 'tier3_news_web';
                        }
                        
                        $q['tier'] = $tier;
                        return $q;
                    }, $queries);

                    return [
                        'queries' => $sanitizedQueries,
                        'context_keywords' => $parsed['context_keywords'] ?? $defaultResult['context_keywords'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('GeminiAnalysisService: Strategy generation failed', ['error' => $e->getMessage()]);
        }

        return $defaultResult;
    }

    /**
     * Analyze search evidences for an alumni using Gemini AI.
     *
     * @param  array  $evidences  Array of search results
     * @param  array  $alumniData Alumni profile data
     * @return array{confidence: float, jabatan: ?string, instansi: ?string, bidang_pekerjaan: ?string, lokasi: ?string, linkedin_url: ?string, notes: string}
     */
    public function analyze(array $evidences, array $alumniData): array
    {
        $defaultResult = [
            'confidence'       => 0.0,
            'jabatan'          => null,
            'instansi'         => null,
            'kategori_pekerjaan'=> null,
            'tipe_posisi'      => null,
            'posisi_sejak'     => null,
            'lokasi'           => null,
            'linkedin_url'     => null,
            'ig_url'           => null,
            'fb_url'           => null,
            'tiktok_url'       => null,
            'email'            => null,
            'no_hp'            => null,
            'is_umm_verified'  => false,
            'umm_evidence'     => null,
            'notes'            => '',
        ];

        if (!$this->isConfigured()) {
            Log::warning('GeminiAnalysisService: API key not configured.');
            return array_merge($defaultResult, ['notes' => 'Gemini API key tidak dikonfigurasi.']);
        }

        if (empty($evidences)) {
            return array_merge($defaultResult, ['notes' => 'Tidak ada evidence untuk dianalisis.']);
        }

        $prompt = $this->buildPrompt($evidences, $alumniData);

        $maxRetries = 3;
        $retryDelay = 2000; // 2 seconds initial delay

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

                $response = Http::timeout(30)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.2,
                    ],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['error'])) {
                        Log::error('GeminiAnalysisService: API returned error field', ['error' => $data['error']]);
                        return array_merge($defaultResult, ['notes' => 'Gemini API Error: ' . ($data['error']['message'] ?? 'Unknown Error')]);
                    }

                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                    $parsed = json_decode($text, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                        return [
                            'confidence'       => (float) ($parsed['confidence'] ?? 0.0),
                            'jabatan'          => $parsed['jabatan'] ?? null,
                            'instansi'         => $parsed['instansi'] ?? null,
                            'kategori_pekerjaan'=> $parsed['kategori_pekerjaan'] ?? null,
                            'tipe_posisi'      => $parsed['tipe_posisi'] ?? null,
                            'posisi_sejak'     => $parsed['posisi_sejak'] ?? null,
                            'lokasi'           => $parsed['lokasi'] ?? null,
                            'linkedin_url'     => $parsed['linkedin_url'] ?? null,
                            'ig_url'           => $parsed['ig_url'] ?? null,
                            'fb_url'           => $parsed['fb_url'] ?? null,
                            'tiktok_url'       => $parsed['tiktok_url'] ?? null,
                            'email'            => $parsed['email'] ?? null,
                            'no_hp'            => $parsed['no_hp'] ?? null,
                            'is_umm_verified'  => (bool) ($parsed['is_umm_verified'] ?? false),
                            'umm_evidence'     => $parsed['umm_evidence'] ?? null,
                            'notes'            => $parsed['notes'] ?? '',
                        ];
                    }

                    Log::warning('GeminiAnalysisService: Failed to parse JSON response', ['text' => $text]);
                    return array_merge($defaultResult, ['notes' => 'Gagal parsing respons AI. Silakan coba lagi.']);
                }

                if ($response->status() === 429 && $attempt < $maxRetries) {
                    Log::warning("GeminiAnalysisService: Rate limited (429). Retrying {$attempt}/{$maxRetries} in {$retryDelay}ms...");
                    usleep($retryDelay * 1000); // usleep takes microseconds
                    $retryDelay *= 2; // Exponential backoff: 2s, 4s
                    continue;
                }

                Log::error('GeminiAnalysisService: API error', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 500),
                ]);

                return array_merge($defaultResult, ['notes' => 'Gemini API error: HTTP ' . $response->status()]);
            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    Log::warning("GeminiAnalysisService: Exception {$e->getMessage()}. Retrying {$attempt}/{$maxRetries} in {$retryDelay}ms...");
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }
                
                Log::error('GeminiAnalysisService: Exception', ['message' => $e->getMessage()]);
                return array_merge($defaultResult, ['notes' => 'Error: ' . $e->getMessage()]);
            }
        }

        return array_merge($defaultResult, ['notes' => 'Gemini API failed after ' . $maxRetries . ' retries.']);
    }

    protected function buildStrategyPrompt($alumni): string
    {
        $namaVariasi = !empty($alumni->nama_variasi) ? 'Nama variasi: ' . implode(', ', $alumni->nama_variasi) : '';

        return <<<PROMPT
Kamu adalah ahli OSINT dan detektif karir. Tugasmu adalah merumuskan strategi pencarian di internet untuk melacak identitas karir alumni universitas berikut (Fase A).

## Profil Alumni
- Nama: {$alumni->nama_lengkap}
- {$namaVariasi}
- NIM: {$alumni->nim}
- Program Studi (Prodi): {$alumni->prodi}
- Fakultas: {$alumni->fakultas}
- Tahun Lulus: {$alumni->tahun_lulus}

## Instruksi
1. **QUERIES**: Buat 4-5 query pencarian Google yang fokus DUA hal saja: Profil Profesional (LinkedIn) dan Publikasi Universitas/Berita. JANGAN sertakan pencarian Instagram/Facebook/Tiktok.
   - Gunakan `site:linkedin.com/in` untuk query profesional.
   - Gunakan parameter UMM/Universitas Muhammadiyah Malang.
   - **PENTING**: Gunakan nilai "tier" ini saja:
     * `tier1_linkedin`
     * `tier4_web`

2. **CONTEXT KEYWORDS**: Buat daftar 5-10 kata kunci konteks (1-3 kata) (seperti UMM, jenis jabatan).

## Format Output (JSON)
{
  "queries": [
    {"query": "\"Nama\" \"Universitas Muhammadiyah Malang\" site:linkedin.com/in", "tier": "tier1_linkedin"}
  ],
  "context_keywords": ["kata_kunci1"]
}
PROMPT;
    }

    protected function buildPrompt(array $evidences, array $alumniData): string
    {
        $evidenceText = '';
        foreach ($evidences as $i => $evidence) {
            $num = $i + 1;
            $evidenceText .= "--- Evidence #{$num} ---\nSource: {$evidence['source_url']}\nType: {$evidence['source_type']}\nSnippet: {$evidence['raw_snippet']}\n\n";
        }

        $namaVariasi = !empty($alumniData['nama_variasi']) ? 'Nama variasi: ' . implode(', ', $alumniData['nama_variasi']) : '';

        return <<<PROMPT
Kamu adalah sistem AI untuk memvalidasi dan mengekstrak informasi karir alumni.

## Profil Alumni Target
- Nama: {$alumniData['nama_lengkap']}
- {$namaVariasi}
- Program Studi: {$alumniData['prodi']}
- Tahun Lulus: {$alumniData['tahun_lulus']}

## Evidence Web
{$evidenceText}

## Instruksi
1. **VALIDASI AFILIASI UMM (WAJIB)**:
   - Pastikan terdapat bukti afiliasi target dengan "Universitas Muhammadiyah Malang" / "UMM".
   - Jika TIDAK ADA afiliasi UMM, confidence WAJIB ≤ 0.30 & is_umm_verified = false.
2. **EKSTRAKSI POSISI (RECENCY ANALYSIS)**:
   - Ambil hanya pekerjaan TERKINI (Present / Current).
   - Abaikan pekerjaan berformat Magang / Internship/ Praktik, kecuali jika itu SATU-SATUNYA yang ditemukan.
   - Tentukan `tipe_posisi`: "current" (aktif), "past", atau "internship_only".
3. **PILIH KATEGORI**: "PNS", "Swasta", atau "Wirausaha".
4. **PENALTI CONFIDENCE**: Beda prodi (-0.2), Lulus beda jauh (-0.15). Max 0.40 jika LinkedIn tanpa mention UMM.

## Format Output (JSON Strict)
{
  "confidence": 0.85,
  "is_umm_verified": true,
  "umm_evidence": "Disebutkan di profil LinkedIn pada history education: UMM",
  "tipe_posisi": "current/past/internship_only",
  "posisi_sejak": "2021",
  "jabatan": "null jika tidak ada",
  "instansi": "null jika tidak ada",
  "kategori_pekerjaan": "Swasta",
  "lokasi": "Jakarta",
  "linkedin_url": "URL atau null",
  "email": "null",
  "no_hp": "null",
  "notes": "Alasan detail terkait identitas"
}
PROMPT;
    }
    public function analyzeSocialMedia(array $evidences, array $context): array
    {
        $defaultResult = [
            'ig_url' => null,
            'fb_url' => null,
            'tiktok_url' => null,
        ];

        if (empty($evidences) || !$this->isConfigured()) return $defaultResult;

        $prompt = $this->buildSocialMediaPrompt($evidences, $context);

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
            $response = Http::timeout(20)->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.1],
            ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsed = json_decode($text, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return array_merge($defaultResult, [
                        'ig_url' => $parsed['ig_url'] ?? null,
                        'fb_url' => $parsed['fb_url'] ?? null,
                        'tiktok_url' => $parsed['tiktok_url'] ?? null,
                    ]);
                }
            }
        } catch (\Exception $e) {
             Log::error('GeminiAnalysis: Social media analysis err', ['msg' => $e->getMessage()]);
        }
        return $defaultResult;
    }

    protected function buildSocialMediaPrompt(array $evidences, array $context): string
    {
        $evidenceText = '';
        foreach ($evidences as $i => $ev) {
            $evidenceText .= "URL: {$ev['source_url']}\nSnippet: {$ev['raw_snippet']}\n\n";
        }
        $instansi = $context['instansi'] ?? '';
        $lokasi = $context['lokasi'] ?? '';
        $nama = $context['nama'] ?? '';

        return <<<PROMPT
Anda adalah spesialis OSINT. Verifikasi apakah profil media sosial berikut milik target ini.
Target Name: {$nama}
Target Job: {$instansi}
Target Location: {$lokasi}
Target University: Universitas Muhammadiyah Malang (UMM)

EVIDENCE:
{$evidenceText}

INSTRUKSI CROSS-REFERENCE (MULTI-SIGNAL MATCHING):
1. Anda wajib mencocokkan snippet URL dengan profil target.
2. Sinyal yang valid: Name match (variasi ok), Location match, Job match, Univ match.
3. JIKA ada min. 2 sinyal cocok -> Ekstrak URL asli.
4. JIKA hanya 1 sinyal cocok (nama saja tanpa konteks) -> KEMBALIKAN NULL.

FORMAT JSON STRICT:
{
  "ig_url": "url instagram target jika valid (atau null)",
  "fb_url": "url facebook target jika valid (atau null)",
  "tiktok_url": "url tiktok target jika valid (atau null)"
}
PROMPT;
    }

    /**
     * V7.2 Defensible Extraction Engine
     * Zero-tolerance data extraction as requested by V7.2 Architecture
     *
     * @param array $evidences
     * @param array $alumniData
     * @return array
     */
    public function analyzeV72(array $evidences, array $alumniData): array
    {
        $defaultResult = [
            'extracted_data' => [
                'company' => null,
                'position' => null,
                'is_umm_alumni' => 'unknown',
            ],
            'social_signals' => [
                'instagram' => [
                    'url' => null,
                    'has_company_mention' => false,
                    'has_linkedin_link' => false,
                ],
            ],
            'extracted_conflicts' => [],
            'alasan_analisis' => 'Tidak ada catatan analisis eksplisit.',
        ];

        if (!$this->isConfigured()) {
            Log::warning('GeminiAnalysisService: API key not configured for V7.2.');
            return $defaultResult;
        }

        if (empty($evidences)) {
            return $defaultResult;
        }

        $prompt = $this->buildV72ExtractionPrompt($evidences, $alumniData);
        $maxRetries = 3;
        $retryDelay = 2000;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
                $response = Http::timeout(30)->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.0, // Forced to absolute 0.0 for Zero-Tolerance Extraction
                    ],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['error'])) {
                        Log::error('GeminiAnalysisService V7.2: API returned error', ['error' => $data['error']]);
                        return $defaultResult;
                    }

                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $parsed = json_decode($text, true);

                    // Strictly parse and sanitize the JSON response with fallback schema
                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                        return [
                            'extracted_data' => [
                                'company' => $parsed['extracted_data']['company'] ?? null,
                                'position' => $parsed['extracted_data']['position'] ?? null,
                                'is_umm_alumni' => $parsed['extracted_data']['is_umm_alumni'] ?? 'unknown',
                            ],
                            'social_signals' => [
                                'instagram' => [
                                    'url' => $parsed['social_signals']['instagram']['url'] ?? null,
                                    'has_company_mention' => (bool) ($parsed['social_signals']['instagram']['has_company_mention'] ?? false),
                                    'has_linkedin_link' => (bool) ($parsed['social_signals']['instagram']['has_linkedin_link'] ?? false),
                                ],
                            ],
                            'extracted_conflicts' => is_array($parsed['extracted_conflicts'] ?? null) 
                                ? $parsed['extracted_conflicts'] 
                                : [],
                            'alasan_analisis' => $parsed['alasan_analisis'] ?? 'Tidak ada catatan analisis eksplisit.',
                        ];
                    }

                    Log::warning('GeminiAnalysisService V7.2: Failed to parse JSON safely', ['text' => $text]);
                    return $defaultResult; // Automatically return default schema on parse error
                }

                if ($response->status() === 429 && $attempt < $maxRetries) {
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }

                Log::error('GeminiAnalysisService V7.2: API error HTTP ' . $response->status());
                return $defaultResult;

            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2;
                    continue;
                }
                Log::error('GeminiAnalysisService V7.2: Exception', ['message' => $e->getMessage()]);
                return $defaultResult;
            }
        }

        return $defaultResult;
    }

    /**
     * Build the EXACT V7.2 Prompt schema as provided in Architecture Document
     */
    protected function buildV72ExtractionPrompt(array $evidences, array $alumniData): string
    {
        $evidenceText = '';
        foreach ($evidences as $i => $evidence) {
            $num = $i + 1;
            $sourceUrl = $evidence['source_url'] ?? 'Unknown URL';
            $snippet = $evidence['raw_snippet'] ?? '';
            $evidenceText .= "--- Snippet {$num} ---\nSource URL: {$sourceUrl}\nContent: {$snippet}\n\n";
        }

        $namaVariasi = !empty($alumniData['nama_variasi']) ? 'Alias: ' . implode(', ', $alumniData['nama_variasi']) : '';
        $tahunLulus = $alumniData['tahun_lulus'] ?? 'Unspecified';

        return <<<PROMPT
TARGET ALUMNI PROFILE:
- Name: {$alumniData['nama_lengkap']}
- {$namaVariasi}
- Major: {$alumniData['prodi']}
- Graduation Year: {$tahunLulus}

WEB EVIDENCES:
{$evidenceText}
The provided snippets have been PRE-FILTERED by a strict PHP system and are highly likely to belong to the target alumni. Your primary task is to deeply analyze these clean snippets, confidently extract the linkedin_url, and accurately distinguish between real professional jobs vs student internships/ambassador roles. Do NOT return null for LinkedIn if a valid matching profile exists. Provide clear human-readable reasoning in alasan_analisis.

You are a Zero-Tolerance Data Extraction Tool. Follow Evidence Hierarchies. Do NOT infer or complete fields.

CRITICAL DIRECTIVES:
1. Identify all companies mentioned across snippets. List them exactly as written.
2. Provide explicit signal extraction for social media. If analyzing an IG/TikTok snippet, search specifically for Company Names or LinkedIn URLs within that snippet's text.
3. If conflicts exist between snippets (e.g. Snippet X says "Shopee", Snippet Y says "Tokopedia"), output BOTH with their respective source URLs into the array "extracted_conflicts" for the Conflict Resolution Engine to handle.

JSON SCHEMA EXPECTED:
{
  "extracted_data": {
    "company": "<primary_ext_match_or_null>",
    "position": "<primary_ext_match_or_null>",
    "is_umm_alumni": "true|false|unknown"
  },
  "social_signals": {
    "instagram": {
       "url": "<url_or_null>",
       "has_company_mention": true|false,
       "has_linkedin_link": true|false
    }
  },
  "extracted_conflicts": [
     {
        "field": "company",
        "value": "<conflicting_value>",
        "source_url": "<source_of_conflict>"
     }
  ],
  "alasan_analisis": "<string: Berikan penjelasan naratif dalam bahasa Indonesia yang mudah dipahami manusia mengenai mengapa Anda memilih pekerjaan ini, mengaitkan bukti, dan alasan penolakan data lain jika ada konflik>"
}
PROMPT;
    }
}
