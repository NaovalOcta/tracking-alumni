# ScoutAlumni (AlumniFinder) v1.5 — Hybrid AI-OSINT Tracking

**ScoutAlumni** (diidentifikasi di antarmuka sebagai **AlumniFinder**) adalah platform cerdas berbasis Hybrid AI-OSINT yang dirancang untuk melacak jejak karir alumni menggunakan Laravel 12. Sistem ini mengintegrasikan teknik pencarian *Multi-Tier Cascade* dengan penalaran AI dari Google Gemini untuk menghasilkan data pelacakan IKU yang akurat dan terverifikasi.

## 🛠️ Tech Stack & Spesifikasi Teknis

Berdasarkan audit internal codebase, berikut adalah spesifikasi teknis yang digunakan:

- **Framework**: [Laravel 12.0 (Stable Edition)](https://laravel.com)
- **Runtime**: PHP ^8.2
- **AI Engine**: [Google Gemini 3.1 Flash Lite (Preview)](https://ai.google.dev/) — Dikonfigurasi untuk validasi timeline karir.
- **Search Infrastructure**: [Serper.dev](https://serper.dev) (Google Search API) dengan batasan 250 query/hari.
- **Frontend Engine**: Vite v7 + [Tailwind CSS v4.0](https://tailwindcss.com) (Modern CSS Configuration).
- **Interactivity**: [Alpine.js](https://alpinejs.dev/) untuk komponen UI reaktif.
- **Database Architecture**: 
  - **Primary Key**: Menggunakan `nim` (string, 15 karakter) sebagai pengidentifikasi unik utama alumni.
  - **Storage Strategy**: MySQL dengan tabel terpisah untuk `evidence_logs` (penyimpanan bukti mentah) dan `tracking_histories` (snapshot perubahan data).

## 🚀 Fitur Utama & Logika Sistem

1.  **Smart-Context Triangulation**: Melakukan validasi silang antara LinkedIn (Tier 1), Google Scholar/GitHub (Tier 2), dan Website Umum/Berita (Tier 3).
2.  **Early-Stop Optimization**: Sistem secara otomatis menghentikan pencarian jika menemukan minimal **3 bukti berkualitas tinggi** di Tier 1 (LinkedIn) yang lolos verifikasi kata kunci kontekstual AI.
3.  **AI Disambiguation**: Gemini AI melakukan analisis "Timeline Logic" untuk memastikan hasil pencarian bukan merupakan orang lain dengan nama yang sama (cek korelasi Tahun Lulus vs Awal Karir).
4.  **Automated Audit Trail**: Setiap pelacakan menghasilkan `confidence_score`. 
    - **Score ≥ 0.8**: Status `auto_verified`.
    - **Score 0.5 - 0.79**: Status `needs_audit` (memerlukan tinjauan manual).
    - **Score < 0.5**: Status `not_found`.
5.  **Batch Processing**: Mendukung pelacakan massal hingga 100 alumni sekaligus menggunakan **Laravel Queue (BatchTrackingJob)**.
6.  **External Automation**: Endpoint khusus di `/automation/run` yang diamankan dengan `CRON_TOKEN` untuk integrasi dengan scheduler eksternal (cron-job.org).

---

## 📊 Matriks Kualitas Aplikasi (Hasil Audit Kode)

| Komponen | Implementasi Teknis | Validasi File |
| :--- | :--- | :--- |
| **Identitas Unik** | Primary Key: `nim` | `2025_03_06_000001_create_alumni_table.php` |
| **Strategi Query** | 3-Tier Cascade Search | `app/Services/QueryGeneratorService.php` |
| **Efisiensi Biaya** | Early-Stop Threshold: 3 Matches | `app/Services/TrackingService.php` (Line 23) |
| **Validasi AI** | Prompt Gemini 3.1 dengan Output JSON | `app/Services/GeminiAnalysisService.php` |
| **Keamanan Cron** | Token-based Authentication via ENV | `routes/web.php` (Line 48) |
| **Monitoring** | Real-time Progress (Cache-polling) | `app/Http/Controllers/TrackingController.php` |

---

## ⚙️ Instalasi & Penggunaan

1.  **Clone & Install**:
    ```bash
    git clone https://github.com/NaovalOcta/tracking-alumni.git
    composer install && npm install
    ```
2.  **Konfigurasi Environment**:
    Pastikan `.env` memiliki key berikut:
    - `SERPER_API_KEY`: API Key Serper.dev
    - `GEMINI_API_KEY`: API Key Google AI Studio
    - `GEMINI_MODEL`: `gemini-3.1-flash-lite-preview`
    - `CRON_TOKEN`: Token unik untuk otomasi (minimal 32 karakter direkomendasikan).
3.  **Database & Assets**:
    ```bash
    php artisan migrate
    npm run build
    ```
4.  **Menjalankan Tracking**:
    - Via Web: Menu **Tracking > Lacak Batch**.
    - Via CLI: `php artisan schedule:run` atau jalankan queue worker `php artisan queue:work`.

---
*Dokumentasi ini dihasilkan melalui audit mendalam terhadap struktur kode dan logika bisnis ScoutAlumni v1.5.*
