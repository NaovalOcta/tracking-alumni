<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export alumni tracking results to CSV.
     */
    public function export(Request $request)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="alumni_tracking_results_' . now()->format('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $query = Alumni::with('latestTrackingResult');

        // Apply filters if any
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('tracking_status', $request->status);
        }

        if ($request->has('prodi') && $request->prodi !== 'all') {
            $query->where('prodi', $request->prodi);
        }

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compliance
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Row
            fputcsv($file, [
                'NIM', 
                'Nama Lengkap', 
                'Prodi', 
                'Tahun Lulus', 
                'Status Tracking',
                'Jabatan',
                'Instansi',
                'Bidang',
                'Lokasi',
                'LinkedIn URL',
                'Skor Kepercayaan',
                'Terakhir Dilacak'
            ]);

            $query->chunk(100, function ($alumni) use ($file) {
                foreach ($alumni as $item) {
                    $result = $item->latestTrackingResult;
                    fputcsv($file, [
                        $item->nim,
                        $item->nama_lengkap,
                        $item->prodi,
                        $item->tahun_lulus,
                        str_replace('_', ' ', strtoupper($item->tracking_status)),
                        $result?->jabatan ?? '',
                        $result?->instansi ?? '',
                        $result?->bidang_pekerjaan ?? '',
                        $result?->lokasi ?? '',
                        $result?->linkedin_url ?? '',
                        $result ? round($result->confidence_score * 100) . '%' : '',
                        $item->last_tracked_at ? $item->last_tracked_at->format('Y-m-d H:i') : ''
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
