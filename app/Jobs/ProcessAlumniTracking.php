<?php

namespace App\Jobs;

use App\Models\Alumni;
use App\Services\TrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAlumniTracking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    public function __construct(
        public string $alumniNim,
    ) {}

    public function handle(TrackingService $trackingService): void
    {
        $alumni = Alumni::find($this->alumniNim);

        if (!$alumni) {
            Log::warning("ProcessAlumniTracking: Alumni not found: {$this->alumniNim}");
            return;
        }

        $result = $trackingService->trackAlumni($alumni);

        Log::info("ProcessAlumniTracking: {$this->alumniNim} — {$result['message']}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessAlumniTracking: Failed for {$this->alumniNim}", [
            'error' => $exception->getMessage(),
        ]);

        // Reset status so it can be retried
        Alumni::where('nim', $this->alumniNim)
            ->update(['tracking_status' => 'belum_dilacak']);
    }
}
