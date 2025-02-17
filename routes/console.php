<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Jobs\FetchImapEmails;
use App\Models\EmailSetting;


// Email sync schedule
Schedule::command('emails:sync')
    ->everyThreeMinutes()
    ->withoutOverlapping()
    ->runInBackground();


// Default inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
