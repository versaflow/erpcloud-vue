<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\SyncImapEmails;

// Use a more reasonable memory limit
ini_set('memory_limit', '256M');

// Register the SyncImapEmails command
Artisan::command('emails:sync', function() {
    $command = new SyncImapEmails();
    return $command->handle();
})->purpose('Sync all enabled IMAP accounts');

// Schedule with better memory management
Schedule::command('emails:sync')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/email-sync.log'))
    ->withoutOverlapping(5)
    ->runInBackground();

// Minimal logging
Schedule::call(function () {
    Log::info('Schedule check: ' . now());
})->everyMinute();

// Default inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
