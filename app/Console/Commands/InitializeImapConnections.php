<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImapConnectionManager;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Log;

class InitializeImapConnections extends Command
{
    protected $signature = 'imap:initialize {--email= : Specific email to initialize}';
    protected $description = 'Initialize IMAP connections';

    public function handle()
    {
        $email = $this->option('email');

        if ($email) {
            $settings = EmailSetting::where('email', $email)->get();
        } else {
            $settings = EmailSetting::where('active', true)->get();
        }

        if ($settings->isEmpty()) {
            $this->error('No email settings found to initialize');
            return 1;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($settings as $setting) {
            try {
                $this->info("Initializing connection for {$setting->email}...");
                
                $connection = ImapConnectionManager::getConnection($setting);
                
                if ($connection) {
                    $successCount++;
                    $this->info("✓ Successfully connected to {$setting->email}");
                } else {
                    $failCount++;
                    $this->error("✗ Failed to connect to {$setting->email}");
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->error("✗ Error connecting to {$setting->email}: {$e->getMessage()}");
                Log::error("IMAP connection failed for {$setting->email}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Summary
        $this->newLine();
        $this->info("Initialization completed:");
        $this->line(" - Successful connections: {$successCount}");
        if ($failCount > 0) {
            $this->error(" - Failed connections: {$failCount}");
            return 1;
        }

        return 0;
    }
}
