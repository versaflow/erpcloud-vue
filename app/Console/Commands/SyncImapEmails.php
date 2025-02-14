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
            
            // Get all enabled email accounts with logging
            $accounts = EmailSetting::where('enabled', true)->get();
            
            $count = $accounts->count();
            $this->info("Found {$count} enabled accounts");
            Log::info("Found enabled email accounts", ['count' => $count]);

            if ($count === 0) {
                $this->warn('No enabled email accounts found to sync');
                Log::warning('No enabled email accounts found for sync');
                return 0;
            }
            
            // Process each account
            foreach ($accounts as $account) {
                try {
                    $this->info("Processing sync for {$account->email}...");
                    Log::info("Starting sync for account", ['email' => $account->email]);
                    
                    FetchImapEmails::dispatchSync($account);
                    
                    $this->info("Successfully synced {$account->email}");
                    Log::info("Sync completed for account", ['email' => $account->email]);
                    
                } catch (\Exception $e) {
                    $this->error("Failed to sync {$account->email}: {$e->getMessage()}");
                    Log::error("Failed to sync email account", [
                        'email' => $account->email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
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
