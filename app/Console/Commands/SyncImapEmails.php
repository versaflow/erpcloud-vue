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
            // Set higher memory limit
            ini_set('memory_limit', '1G');
            
            $this->info('Starting IMAP sync: ' . now());
            Log::info('SyncImapEmails command started');
            
            // Mark cron as running
            cache()->put('last_cron_run', now());
            
            // Use chunking for better memory management
            EmailSetting::where('enabled', true)
                ->chunkById(5, function($accounts) {
                    foreach ($accounts as $account) {
                        try {
                            $this->info("Processing sync for {$account->email}...");
                            Log::info("Starting sync for account", ['email' => $account->email]);
                            
                            FetchImapEmails::dispatchSync($account);
                            
                            $this->info("Successfully synced {$account->email}");
                            Log::info("Sync completed for account", ['email' => $account->email]);
                            
                            // Force garbage collection after each account
                            gc_collect_cycles();
                            
                        } catch (\Exception $e) {
                            $this->error("Failed to sync {$account->email}: {$e->getMessage()}");
                            Log::error("Failed to sync email account", [
                                'email' => $account->email,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                });
            
            return 0;
            
        } catch (\Exception $e) {
            $message = "Fatal error in email sync: {$e->getMessage()}";
            $this->error($message);
            Log::error($message, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}
