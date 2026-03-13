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
            'bidang_pekerjaan' => null,
            'lokasi'           => null,
            'linkedin_url'     => null,
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
                            'bidang_pekerjaan' => $parsed['bidang_pekerjaan'] ?? null,
                            'lokasi'           => $parsed['lokasi'] ?? null,
                            'linkedin_url'     => $parsed['linkedin_url'] ?? null,
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

    /**
     * Build the strategy prompt for Gemini.
     */
    protected function buildStrategyPrompt($alumni): string
    {
        $namaVariasi = !empty($alumni->nama_variasi) ? 'Nama variasi: ' . implode(', ', $alumni->nama_variasi) : '';

        return <<<PROMPT
Kamu adalah ahli OSINT dan detektif karir. Tugasmu adalah merumuskan strategi pencarian di internet untuk melacak alumni universitas berikut:

## Profil Alumni
- Nama: {$alumni->nama_lengkap}
- {$namaVariasi}
- NIM: {$alumni->nim}
- Program Studi (Prodi): {$alumni->prodi}
- Fakultas: {$alumni->fakultas}
- Tahun Lulus: {$alumni->tahun_lulus}

## Instruksi
1. **QUERIES**: Buat 6-8 query pencarian Google yang efektif untuk menemukan profil karir atau LinkedIn alumni ini.
   - Gunakan operator pencarian seperti `site:linkedin.com/in`, `site:scholar.google.com`, `site:github.com`.
   - Gunakan tanda kutip untuk nama lengkap.
   - Variasikan query dengan menyatukan nama dengan kata kunci prodi, atau fakultas.
   - Targetkan Tier 1 (LinkedIn), Tier 2 (Akademik), dan Tier 3 (General Web).
   - **PENTING**: Anda WAJIB menggunakan nilai "tier" berikut secara eksak:
     * `tier1_linkedin` (untuk LinkedIn)
     * `tier2_scholar_github` (untuk Scholar, ResearchGate, GitHub)
     * `tier3_news_web` (untuk Portal Berita, Web Organisasi, atau Web Umum)

2. **CONTEXT KEYWORDS**: Buat daftar 10-15 kata kunci spesifik (1-3 kata) yang kemungkinan besar muncul di profil profesional/sosial alumni ini.
   - Masukkan singkatan universitas yang umum (misal: UI, ITB, UGM jika relevan - asumsi lokal Indonesia).
   - Masukkan nama jabatan/posisi yang lazim untuk lulusan prodi tersebut (misal: Farmasi -> Apoteker, TTK, QC, Farmakologi).
   - Masukkan istilah industri terkait prodi tersebut.
   - JANGAN masukkan kata generik seperti "pendidikan", "pengalaman", "tentang".
   - Kata kunci ini akan digunakan untuk memvalidasi apakah hasil pencarian (seperti LinkedIn) benar-benar milik alumni yang kita cari.

## Format Output (JSON)
Jawab HANYA dalam format JSON berikut:
{
  "queries": [
    {"query": "\"Nama\" site:linkedin.com/in", "tier": "tier1_linkedin"},
    ...
  ],
  "context_keywords": ["kata_kunci1", "kata_kunci2", ...]
}
PROMPT;
    }

    /**
     * Build the analysis prompt for Gemini.
     */
    protected function buildPrompt(array $evidences, array $alumniData): string
    {
        $evidenceText = '';
        foreach ($evidences as $i => $evidence) {
            $num = $i + 1;
            $evidenceText .= "--- Evidence #{$num} ---\n";
            $evidenceText .= "Source: {$evidence['source_url']}\n";
            $evidenceText .= "Type: {$evidence['source_type']}\n";
            $evidenceText .= "Snippet: {$evidence['raw_snippet']}\n\n";
        }

        $namaVariasi = '';
        if (!empty($alumniData['nama_variasi'])) {
            $namaVariasi = 'Nama variasi: ' . implode(', ', $alumniData['nama_variasi']);
        }

        return <<<PROMPT
Kamu adalah sistem AI untuk memvalidasi dan mengekstrak informasi karir alumni universitas.

## Profil Alumni Target
- Nama: {$alumniData['nama_lengkap']}
- {$namaVariasi}
- NIM: {$alumniData['nim']}
- Program Studi: {$alumniData['prodi']}
- Tahun Lulus: {$alumniData['tahun_lulus']}

## Evidence dari Pencarian Web
{$evidenceText}

## Instruksi
1. **VALIDASI**: Tentukan apakah evidence di atas benar merujuk ke alumni yang sama (bukan orang lain dengan nama mirip). Perhatikan kesesuaian tahun lulus vs tahun mulai karir (logika kewajaran).
2. **EKSTRAKSI**: Jika valid, ekstrak informasi terkini tentang:
   - Jabatan/posisi terkini
   - Instansi/perusahaan
   - Bidang pekerjaan/industri
   - Lokasi (kota/negara)
   - URL LinkedIn (jika ada)
3. **CONFIDENCE**: Berikan skor kepercayaan 0.0-1.0 berdasarkan:
   - 0.8-1.0: Data sangat meyakinkan, multiple sumber konsisten
   - 0.5-0.79: Data cukup meyakinkan tapi perlu review manual
   - 0.0-0.49: Data tidak cukup atau meragukan
4. **CONFLICT RESOLUTION**: Jika ada perbedaan antar sumber, pilih data terbaru (Recency Analysis).

## Format Output (JSON)
Jawab HANYA dalam format JSON berikut, tanpa teks tambahan:
{
  "confidence": 0.0,
  "jabatan": "string atau null",
  "instansi": "string atau null",
  "bidang_pekerjaan": "string atau null",
  "lokasi": "string atau null",
  "linkedin_url": "string atau null",
  "notes": "penjelasan singkat tentang analisis dan alasan confidence score"
}
PROMPT;
    }
}
