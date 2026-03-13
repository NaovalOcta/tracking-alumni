# **Rancangan Aplikasi: ScoutAlumni v1.5 (Hybrid AI-OSINT)**

Rancangan ini merupakan penggabungan (sintesis) antara metodologi OSINT konvensional dengan pemrosesan bahasa alami (LLM) untuk akurasi maksimal dan efisiensi biaya.

## **1\. Algoritma Utama: "Smart-Context Triangulation"**

Sistem beroperasi dalam siklus hidup data sebagai berikut:

1. **Profil Target (Data Preparation)**:
    - Sistem menyusun profil awal: Nama (beserta variasi: M. Rizky, Muhammad Rizky), Prodi, dan Tahun Lulus.

    - Menetapkan status awal: "Belum Dilacak".

2. **Scheduler & Prioritization**:
    - Sistem menjalankan _job_ harian dengan skala prioritas:
        1. Alumni baru belum terlacak.

        2. Alumni dengan status "Insufficient Data".

        3. Alumni yang sudah \> 6 bulan tidak diperbarui.

3. **Dynamic Query Generation**:
    - Sistem tidak hanya mencari nama, tapi membuat variasi query:
        - "Nama Lengkap" UMM Informatika

        - "Nama Lengkap" site:scholar.google.com

        - "Variasi Nama" "Software Engineer" Malang

4. **Multi-Tier Cascade Search**:
    - Mencari secara bertahap (LinkedIn → GitHub/Scholar → Berita/Web Resmi) menggunakan Google Custom Search API.

5. **AI Analysis & Disambiguation (Gemini API)**:
    - **Validation**: Memastikan orang di hasil pencarian adalah alumni yang benar (Cek kesesuaian Tahun Lulus vs Tahun Mulai Karir).

    - **Extraction**: Mengambil Jabatan, Instansi, dan Lokasi.

    - **Conflict Resolution**: Memilih data terbaru jika ada perbedaan antar sumber (Recency Analysis).

6. **Historical Storage**:
    - Menyimpan hasil sebagai "Jejak Bukti" (Link, Snippet, Skor Kepercayaan).

    - Menyimpan versi lama ke dalam tabel riwayat (Snapshot) sebelum melakukan update.

## **2\. Pseudocode Sistem (Full Workflow)**

PROCEDURE Main_Alumni_Scout:  
 // Ambil target berdasarkan prioritas waktu dan status  
 target_list \= Database.Get_Priority_Queue(limit=50)

    FOR EACH alumni IN target\_list:
        evidences \= \[\]

        // 1\. GENERATE QUERIES (Berdasarkan Langkah 4 Dosen)
        queries \= Generate\_Variasi\_Query(alumni)

        // 2\. FETCH DATA (Cascade Search)
        FOR EACH q IN queries:
            raw\_result \= Google\_API.Search(q)
            IF raw\_result \!= NULL:
                evidences.add(raw\_result)
        ENDFOR

        // 3\. AI REASONING (Langkah 7-9 Dosen \+ Conflict Resolution)
        analysis \= Gemini\_API.Analyze(evidences, alumni)

        IF analysis.confidence \> 0.8:
            // Cek jika ada data lama untuk dipindahkan ke History
            IF Database.Has\_Old\_Data(alumni.id):
                Database.Archive\_To\_History(alumni.id)
            ENDIF

            Database.Update(alumni.id, {
                data: analysis.final\_data,
                status: "AUTO\_VERIFIED",
                last\_update: CURRENT\_DATE
            })

        ELSE IF analysis.confidence \> 0.5:
            Database.Update(alumni.id, {
                data: analysis.probable\_data,
                status: "NEEDS\_AUDIT",
                notes: analysis.conflict\_explanation
            })

        ELSE:
            Database.Update(alumni.id, { status: "NOT\_FOUND\_OR\_MINIMAL" })
        ENDIF
    ENDFOR

END PROCEDURE

## **3\. Use Case Diagram (Detailed)**

| Aktor           | Use Case               | Deskripsi                                                                  |
| :-------------- | :--------------------- | :------------------------------------------------------------------------- |
| **Admin Prodi** | **Konfigurasi Sumber** | Menentukan prioritas (misal: Scholar lebih penting bagi prodi Kedokteran). |
|                 | **Review Audit Trail** | Melihat perbandingan bukti yang menyebabkan status "Needs Audit".          |
|                 | **Export Data IKU**    | Mengunduh hasil valid untuk kebutuhan akreditasi.                          |
| **Sistem (AI)** | **Query Optimizer**    | Mengubah nama alumni menjadi berbagai variasi pencarian cerdas.            |
|                 | **Conflict Resolver**  | Menghitung probabilitas kebenaran data jika terjadi kontradiksi sumber.    |

## **4\. Analisis Akurasi & Efisiensi**

### **Mengapa Akurat?**

- **Triangulasi**: Tidak percaya pada satu sumber. Jika LinkedIn dan Berita Kampus mengatakan hal yang sama, validitas naik.

- **Timeline Logic**: AI dilatih untuk menolak data jika seseorang diklaim jadi Senior Manager hanya 1 tahun setelah lulus (logika kewajaran).

### **Mengapa Efisien?**

- **Token-Based API**: Menggunakan Gemini Flash yang murah/gratis dengan instruksi ekstraksi yang sangat spesifik (Prompt Engineering).

- **Selective Crawling**: Hanya melakukan pencarian mendalam jika data di Tier 1 (LinkedIn) tidak memadai.

## **5\. Struktur Penyimpanan Bukti (Evidence Log)**

Untuk memenuhi Langkah 10 (Jejak Bukti), setiap temuan disimpan dalam format:

- alumni_id: FK ke tabel master.

- source_url: Link asli temuan.

- raw_snippet: Teks mentah dari hasil pencarian (sebagai bukti audit).

- extracted_json: Hasil ekstraksi AI.

- timestamp_found: Waktu pelacakan dilakukan.
