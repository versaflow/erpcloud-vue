<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\SyncImapEmails;

// Register the SyncImapEmails command
Artisan::command('emails:sync', function() {
    $command = new SyncImapEmails();
    return $command->handle();
})->purpose('Sync all enabled IMAP accounts')
  ->name('Sync IMAP Emails');

// Schedule email sync
Schedule::command('emails:sync')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/email-sync.log'))
    ->before(function () {
        Log::info('Starting scheduled email sync');
    })
    ->after(function () {
        Log::info('Completed scheduled email sync');
    })
    ->withoutOverlapping();

// Keep debug logging
Schedule::call(function () {
    Log::info('Schedule test ran at: ' . now());
})->everyMinute();

// Default inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
