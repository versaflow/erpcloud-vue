<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImapConnectionManager;

class InitializeImapConnections extends Command
{
    protected $signature = 'imap:initialize';
    protected $description = 'Initialize persistent IMAP connections';

    public function handle()
    {
        $this->info('Initializing IMAP connections...');
        ImapConnectionManager::initializeAllConnections();
        $this->info('Done!');
    }
}
