<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Setting;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dynamic Scheduling for Alumni Tracking
Schedule::call(function () {
    $frequency = Setting::where('key', 'tracking_frequency')->value('value') ?? 'daily';
    $time = Setting::where('key', 'tracking_time')->value('value') ?? '00:00';
    $dayOfWeek = Setting::where('key', 'tracking_day_of_week')->value('value') ?? '1';
    $dayOfMonth = Setting::where('key', 'tracking_day_of_month')->value('value') ?? '1';
    
    // Status check
    $enabled = Setting::where('key', 'tracking_enabled')->value('value') ?? '1';

    if ($enabled !== '1') {
        return;
    }

    $now = now();
    $currentHourMin = $now->format('H:i');

    if ($currentHourMin !== $time) {
        return;
    }

    $shouldRun = match ($frequency) {
        'daily' => true,
        'weekly' => (string) $now->dayOfWeek === (string) $dayOfWeek,
        'monthly' => (string) $now->day === (string) $dayOfMonth,
        default => false,
    };

    if ($shouldRun) {
        $arguments = [];
        if (!empty($prodi)) $arguments['--prodi'] = $prodi;
        if (!empty($year)) $arguments['--year'] = $year;
        
        Artisan::call('app:run-alumni-tracking', $arguments);
    }
})->everyMinute();
