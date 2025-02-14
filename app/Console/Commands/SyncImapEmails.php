<?php

namespace App\Console\Commands;

use App\Models\EmailSetting;
use App\Jobs\FetchImapEmails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncImapEmails extends Command
{
    protected $signature = 'emails:sync';
    protected $description = 'Sync all enabled IMAP accounts';

    public function handle()
    {
        try {
            // Use reasonable memory limit
            ini_set('memory_limit', '256M');
            
            Log::info('Starting sync');
            
            // Process in smaller chunks
            EmailSetting::where('enabled', true)
                ->chunkById(2, function($accounts) {
                    foreach ($accounts as $account) {
                        try {
                            FetchImapEmails::dispatch($account); // Use queue instead of dispatchSync
                            $this->info("Queued sync for {$account->email}");
                            
                        } catch (\Exception $e) {
                            Log::error("Failed to queue {$account->email}: {$e->getMessage()}");
                        }
                        
                        gc_collect_cycles(); // Clean up after each account
                    }
                });
            
            return 0;
            
        } catch (\Exception $e) {
            Log::error("Sync error: " . $e->getMessage());
            return 1;
        }
    }
}
