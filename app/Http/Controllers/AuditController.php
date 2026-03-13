<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\TrackingResult;
use App\Models\TrackingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    /**
     * Display a listing of alumni that need audit.
     */
    public function index(Request $request)
    {
        $query = Alumni::with('latestTrackingResult')
            ->where('tracking_status', 'needs_audit');

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $alumni = $query->paginate(10)->withQueryString();

        return view('audit.index', compact('alumni'));
    }

    /**
     * Manually verify a tracking result.
     */
    public function verify(Request $request, string $nim)
    {
        $alumni = Alumni::findOrFail($nim);
        $result = $alumni->latestTrackingResult;

        if (!$result) {
            return back()->with('error', 'Hasil tracking tidak ditemukan.');
        }

        // Update result with verification info
        $result->update([
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // Update alumni status
        $alumni->update(['tracking_status' => 'auto_verified']); // Or use 'verified' if we want to distinguish

        // Add history entry for audit
        TrackingHistory::create([
            'alumni_nim' => $alumni->nim,
            'snapshot_data' => $result->toArray(),
            'changed_reason' => 'Verifikasi manual oleh ' . Auth::user()->name,
            'created_at' => now(),
        ]);

        return back()->with('success', "Data {$alumni->nama_lengkap} berhasil diverifikasi.");
    }

    /**
     * Reject or mark as not found.
     */
    public function reject(Request $request, string $nim)
    {
        $alumni = Alumni::findOrFail($nim);

        $alumni->update(['tracking_status' => 'not_found']);

        return back()->with('info', "Data {$alumni->nama_lengkap} ditandai sebagai tidak ditemukan.");
    }
}
