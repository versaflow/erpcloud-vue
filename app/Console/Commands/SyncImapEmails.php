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
            $this->info('Starting IMAP sync: ' . now());
            Log::info('SyncImapEmails command started');
            
            // Mark cron as running
            cache()->put('last_cron_run', now());
            
            // Get all enabled email accounts
            $accounts = EmailSetting::where('enabled', true)->get();
            
            $this->info("Found {$accounts->count()} enabled accounts");
            Log::info("Found enabled email accounts", ['count' => $accounts->count()]);
            
            $successCount = 0;
            $failureCount = 0;
            
            // Process each account
            foreach ($accounts as $account) {
                try {
                    $this->info("Syncing {$account->email}...");
                    Log::info("Starting sync for account", ['email' => $account->email]);
                    
                    // Use dispatchSync for immediate processing
                    FetchImapEmails::dispatchSync($account);
                    
                    $successCount++;
                    $this->info("Successfully synced {$account->email}");
                    Log::info("Sync completed for account", ['email' => $account->email]);
                    
                } catch (\Exception $e) {
                    $failureCount++;
                    $this->error("Failed to sync {$account->email}: {$e->getMessage()}");
                    Log::error("Failed to sync email account", [
                        'email' => $account->email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Log final results
            $summary = "Sync completed. Success: $successCount, Failed: $failureCount";
            $this->info($summary);
            Log::info("Email sync completed", [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total_accounts' => $accounts->count()
            ]);
            
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
