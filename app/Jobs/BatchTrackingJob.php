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
     * @param  string|null  $prodi  Filter by prodi
     * @param  int|null  $year   Filter by graduation year
     * @param  int|null  $userId The user who triggered the tracking (for notifications)
     */
    public function __construct(
        public ?string $status = 'belum_dilacak',
        public int $limit = 50,
        public ?string $prodi = null,
        public ?int $year = null,
        public ?int $userId = null,
    ) {}

    public function handle(): void
    {
        $query = Alumni::query();

        if ($this->status) {
            $query->status($this->status);
        }

        if ($this->prodi) {
            $query->where('prodi', $this->prodi);
        }

        if ($this->year) {
            $query->where('tahun_lulus', $this->year);
        }

        $alumni = $query->limit($this->limit)->get();

        Log::info("BatchTrackingJob: Dispatching {$alumni->count()} individual tracking jobs. Filter - Status: {$this->status}, Prodi: {$this->prodi}, Year: {$this->year}");

        foreach ($alumni as $index => $alum) {
            ProcessAlumniTracking::dispatch($alum->nim)
                ->delay(now()->addSeconds($index * 2));
        }

        // Logic for notification will be added later when individual jobs are finished
        // Or we can notify when the BATCH DISPATCH is done if it's large.
        // For Phase 4, we want "Notification when tracking finished". 
        // This is tricky because individual jobs are async.
        // We might need a way to track the batch completion.
    }
}
