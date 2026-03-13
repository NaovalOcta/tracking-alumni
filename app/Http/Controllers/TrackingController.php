<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\TrackingResult;
use App\Jobs\BatchTrackingJob;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        // Recent tracking activity
        $recentTracking = Alumni::with('latestTrackingResult')
            ->whereNotNull('last_tracked_at')
            ->orderByDesc('last_tracked_at')
            ->take(10)
            ->get();

        // All alumni for single tracking dropdown (can re-track any)
        $readyForTracking = Alumni::orderBy('nama_lengkap')->get();

        return view('tracking.index', compact('apiStatus', 'stats', 'recentTracking', 'readyForTracking'));
    }

    /**
     * Start tracking for a single alumni (synchronous).
     */
    public function trackSingle(Request $request, TrackingService $trackingService, string $nim)
    {
        $alumni = Alumni::findOrFail($nim);

        if ($request->ajax()) {
            // Dispatch to queue and return immediately so UI can poll
            \App\Jobs\ProcessAlumniTracking::dispatch($alumni->nim);
            return response()->json(['status' => 'queued', 'message' => 'Tracking dimulai.']);
        }

        // Fallback for non-ajax
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
            if ($request->ajax()) {
                return response()->json(['error' => "Tidak ada alumni dengan status \"{$status}\" untuk dilacak."], 422);
            }
            return redirect()->route('tracking.index')
                ->with('error', "Tidak ada alumni dengan status \"{$status}\" untuk dilacak.");
        }

        // Get the list of NIMs that will be processed
        $alumniToProcess = Alumni::status($status)->limit($limit)->pluck('nim');

        BatchTrackingJob::dispatch($status, $limit);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'started', 
                'message' => "Batch tracking dimulai untuk {$limit} alumni.",
                'nims' => $alumniToProcess
            ]);
        }

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

    /**
     * Get real-time progress from cache.
     */
    public function getProgress(string $nim)
    {
        $progress = Cache::get("tracking_progress_{$nim}");

        if (!$progress) {
            return response()->json(['status' => 'not_found']);
        }

        return response()->json($progress);
    }
}
