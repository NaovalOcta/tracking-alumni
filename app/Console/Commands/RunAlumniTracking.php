<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BatchTrackingJob;

class RunAlumniTracking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-alumni-tracking {--prodi= : Filter by prodi} {--year= : Filter by graduation year} {--limit=50 : Max alumni to process} {--status=belum_dilacak : Initial status to track}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run batch alumni tracking with optional filters';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $prodi = $this->option('prodi');
        $year = $this->option('year');
        $limit = (int) $this->option('limit');
        $status = $this->option('status');

        $this->info("Starting batch alumni tracking...");
        if ($prodi) $this->line("Filter Prodi: {$prodi}");
        if ($year) $this->line("Filter Year: {$year}");
        $this->line("Limit: {$limit}");

        BatchTrackingJob::dispatch($status, $limit, $prodi, $year);

        $this->info('Batch tracking job dispatched to queue.');
    }
}
