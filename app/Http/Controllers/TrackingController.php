<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\TrackingResult;
use App\Jobs\BatchTrackingJob;
use App\Services\TrackingService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Tracking monitor — shows status overview and controls.
     */
    public function index(TrackingService $trackingService)
    {
        $apiStatus = $trackingService->isReady();

        $stats = [
            'total'           => Alumni::count(),
            'belum_dilacak'   => Alumni::status('belum_dilacak')->count(),
            'sedang_dilacak'  => Alumni::status('sedang_dilacak')->count(),
            'auto_verified'   => Alumni::status('auto_verified')->count(),
            'needs_audit'     => Alumni::status('needs_audit')->count(),
            'not_found'       => Alumni::status('not_found')->count(),
        ];

        // Recent tracking results
        $recentResults = TrackingResult::with('alumni')
            ->latest('updated_at')
            ->take(10)
            ->get();

        // All alumni for single tracking dropdown (can re-track any)
        $readyForTracking = Alumni::orderBy('nama_lengkap')->get();

        return view('tracking.index', compact('apiStatus', 'stats', 'recentResults', 'readyForTracking'));
    }

    /**
     * Start tracking for a single alumni (synchronous).
     */
    public function trackSingle(Request $request, TrackingService $trackingService, string $nim)
    {
        $alumni = Alumni::findOrFail($nim);

        // Run synchronously for immediate feedback
        $result = $trackingService->trackAlumni($alumni);

        return redirect()->route('tracking.index')
            ->with('success', "Tracking {$alumni->nama_lengkap}: {$result['message']}");
    }

    /**
     * Dispatch batch tracking job (asynchronous via queue).
     */
    public function trackBatch(Request $request)
    {
        $status = $request->input('status', 'belum_dilacak');
        $limit = min((int) $request->input('limit', 50), 100);

        $count = Alumni::status($status)->count();

        if ($count === 0) {
            return redirect()->route('tracking.index')
                ->with('error', "Tidak ada alumni dengan status \"{$status}\" untuk dilacak.");
        }

        BatchTrackingJob::dispatch($status, $limit);

        return redirect()->route('tracking.index')
            ->with('success', "Batch tracking dimulai untuk {$limit} alumni (status: {$status}). Tracking akan berjalan di background.");
    }

    /**
     * Show tracking result detail for a specific alumni.
     */
    public function result(string $nim)
    {
        $alumni = Alumni::with(['trackingResults', 'evidenceLogs', 'searchQueries', 'trackingHistories'])
            ->findOrFail($nim);

        $latestResult = $alumni->trackingResults->sortByDesc('updated_at')->first();
        $searchQueries = $alumni->searchQueries->sortByDesc('searched_at');
        $evidenceLogs = $alumni->evidenceLogs->sortByDesc('searched_at');
        $histories = $alumni->trackingHistories->sortByDesc('created_at');

        return view('tracking.result', compact('alumni', 'latestResult', 'searchQueries', 'evidenceLogs', 'histories'));
    }
}
