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
                    return array_merge($defaultResult, ['notes' => 'Gagal parsing respons AI: ' . substr($text, 0, 200)]);
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
