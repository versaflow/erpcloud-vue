<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// Register the emails:sync command
Artisan::command('emails:sync', function() {
    $this->info('Starting sync at: ' . now());
    Log::info('SyncImapEmails started');
    
    // Simple test to verify it's running
    cache()->put('last_cron_run', now());
    
    $this->info('Sync completed');
    return 0;
})->purpose('Sync all enabled IMAP accounts');

// Define the schedule
Schedule::command('emails:sync')
    ->name('Sync IMAP Emails')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/email-sync.log'))
    ->withoutOverlapping();

// Add debug logging
Schedule::call(function () {
    Log::info('Schedule test ran at: ' . now());
})->everyMinute();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
