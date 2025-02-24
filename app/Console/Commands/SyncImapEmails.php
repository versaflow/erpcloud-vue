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
            ini_set('memory_limit', '256M');
            
            Log::channel('email-sync')->info('Scheduled sync - PHP Environment:', [
                'php_version' => PHP_VERSION,
                'server_api' => php_sapi_name(),
                'memory_limit' => ini_get('memory_limit'),
                'loaded_extensions' => get_loaded_extensions()
            ]);
            
            $accounts = EmailSetting::where('enabled', true)->get();
            
            foreach ($accounts as $account) {
                try {
                    $this->info("Processing {$account->email}");
                    Log::channel('email-sync')->info("Starting sync for {$account->email}", [
                        'php_version' => PHP_VERSION,
                        'memory_limit' => ini_get('memory_limit')
                    ]);
                    
                    $fetcher = new FetchImapEmails($account);
                    $fetcher->handle();
                    
                    Log::channel('email-sync')->info("Completed sync for {$account->email}");
                    $this->info("✓ Completed {$account->email}");
                    
                } catch (\Exception $e) {
                    Log::channel('email-sync')->error("Failed processing {$account->email}", [
                        'error' => $e->getMessage(),
                        'php_version' => PHP_VERSION,
                        'server_api' => php_sapi_name()
                    ]);
                    $this->error("✗ Failed {$account->email}: {$e->getMessage()}");
                }
                
                gc_collect_cycles();
            }
            
            return 0;
            
        } catch (\Exception $e) {
            Log::channel('email-sync')->error("Sync error", [
                'error' => $e->getMessage(),
                'php_version' => PHP_VERSION,
                'server_api' => php_sapi_name()
            ]);
            return 1;
        }
    }
}
