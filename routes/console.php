<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\SyncImapEmails;

// Increase memory limit globally for CLI
ini_set('memory_limit', '1G');

// Register command directly without closure
Artisan::starting(function ($artisan) {
    $artisan->resolve(SyncImapEmails::class);
});

// Schedule email sync with memory management
Schedule::command('emails:sync')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/email-sync.log'))
    ->before(function () {
        gc_collect_cycles(); // Clean up before run
        Log::info('Starting scheduled email sync');
    })
    ->after(function () {
        Log::info('Completed scheduled email sync');
        gc_collect_cycles(); // Clean up after run
    })
    ->withoutOverlapping()
    ->runInBackground(); // Run in background to prevent memory issues

// Keep debug logging minimal
Schedule::call(function () {
    Log::info('Schedule check: ' . now());
})->everyMinute();

// Default inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
