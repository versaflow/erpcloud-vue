<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncImapEmails extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'emails:sync';

    /**
     * The console command description.
     */
    protected $description = 'Sync all enabled IMAP accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting sync at: ' . now());
        Log::info('SyncImapEmails started');
        
        // Simple test to verify it's running
        cache()->put('last_cron_run', now());
        
        $this->info('Sync completed');
        return 0;
    }
}
