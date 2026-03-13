<?php

namespace App\Jobs;

use App\Models\Alumni;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * @param  string|null  $status  Filter by tracking status (default: belum_dilacak)
     * @param  int  $limit  Max number of alumni to process
     */
    public function __construct(
        public ?string $status = 'belum_dilacak',
        public int $limit = 50,
    ) {}

    public function handle(): void
    {
        $query = Alumni::query();

        if ($this->status) {
            $query->status($this->status);
        }

        $alumni = $query->limit($this->limit)->get();

        Log::info("BatchTrackingJob: Dispatching {$alumni->count()} individual tracking jobs (filter: {$this->status})");

        foreach ($alumni as $index => $alum) {
            ProcessAlumniTracking::dispatch($alum->nim)
                ->delay(now()->addSeconds($index * 2));
        }
    }
}
