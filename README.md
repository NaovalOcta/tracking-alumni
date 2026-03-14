# ScoutAlumni (AlumniFinder) v1.5

ScoutAlumni is a hybrid AI-OSINT web application designed to track and verify alumni career data. It synthesizes conventional OSINT methodologies with Natural Language Processing (Gemini AI) for maximum accuracy and cost efficiency.

## 🚀 Core Algorithm: "Smart-Context Triangulation"

The system operates through a sophisticated data cycle to ensure high-fidelity tracking:

1.  **Profil Target (Data Preparation)**: Compiles initial profiles with name variations, study programs, and graduation years.
2.  **Scheduler & Prioritization**: Runs daily jobs prioritizing new alumni, those with insufficient data, or stale records (>6 months).
3.  **Dynamic Query Generation**: Leverages Gemini AI to formulate intelligent search query variations (e.g., using site operators like LinkedIn, Google Scholar, and GitHub).
4.  **Multi-Tier Cascade Search**: Executes gradual searches using Google Custom Search (via Serper.dev):
    *   **Tier 1**: LinkedIn (Professional)
    *   **Tier 2**: GitHub/Google Scholar (Academic/Technical)
    *   **Tier 3**: News/Official Web portals.
5.  **AI Analysis & Disambiguation (Gemini AI)**: 
    *   **Validation**: Confirms identity by checking graduation year against career start dates (Timeline Logic).
    *   **Extraction**: Captures Job Title, Instance, and Location.
    *   **Conflict Resolution**: Applies Recency Analysis to select the most relevant data.
6.  **Historical Storage**: Maintains an "Evidence Trace" (Links, Snippets, Confidence Scores) and archives old snapshots before updates.

## 🛠️ Technology Stack

*   **Backend**: Laravel 11.x (PHP)
*   **Database**: MySQL (Alumni Master, Evidence Logs, Tracking Results)
*   **Search Infrastructure**: [Serper.dev](https://serper.dev/) (Google Search API)
*   **Intelligence Engine**: [Google Gemini AI](https://deepmind.google/technologies/gemini/) (Model: `gemini-1.5-flash` for efficiency and `gemini-1.5-pro` for deep analysis)
*   **Styling**: Vanilla CSS with modern aesthetics (Glassmorphism, Dark Mode support)

## 📦 Key Components

*   **`TrackingService`**: Orchestrates the full search-to-analysis workflow.
*   **`GeminiAnalysisService`**: Handles strategy generation and evidence analysis via Gemini API.
*   **`QueryGeneratorService`**: Formulates AI-powered or static fallback search queries.
*   **`SerperSearchService`**: Manages interaction with the Google Search API.

## ✅ Quality Testing Results

The application has been verified against the quality aspects defined in the design document.

| Aspek Kualitas | Kriteria Uji | Hasil Evaluasi | Status |
| :--- | :--- | :--- | :--- |
| **Akurasi (Triangulation)** | Triangulasi data dari berbagai sumber (LinkedIn, Scholar, Web). | `TrackingService` mengimplementasikan multi-tier search (Tier 1-3). Data divalidasi silang menggunakan Gemini AI. | ✅ Pass |
| **Akurasi (Timeline Logic)** | Verifikasi kesesuaian tahun lulus vs awal karir. | Prompt Gemini di `GeminiAnalysisService` secara eksplisit menginstruksikan AI untuk memeriksa logika timeline karir. | ✅ Pass |
| **Efisiensi (Token-Based API)** | Penggunaan model AI yang hemat biaya. | Implementasi menggunakan `gemini-1.5-flash` (atau versi flash lainnya) yang memiliki latensi rendah dan biaya token efisien. | ✅ Pass |
| **Efisiensi (Selective Crawling)** | Mekanisme *Early-stop* untuk menghemat API call. | `TrackingService` memiliki `earlyStopThreshold` (default: 3). Jika data LinkedIn (Tier 1) sudah mencukupi, pencarian tier lain dihentikan. | ✅ Pass |
| **Reliabilitas (Evidence Log)** | Traceability temuan melalui log bukti. | Temuan disimpan secara detail di tabel `evidence_logs` mencakup URL, snippet mentah, dan tipe sumber untuk audit manual. | ✅ Pass |
| **Reliabilitas (Conflict Resolution)** | Penanganan kontradiksi data antar sumber. | `GeminiAnalysisService` menggunakan teknik *Recency Analysis* dalam prompt untuk memilih data terbaru jika terjadi konflik informasi. | ✅ Pass |

---
*Created with focus on Indonesian Higher Education IKU (Indikator Kinerja Utama) requirements.*
