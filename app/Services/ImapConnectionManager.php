<?php

namespace App\Services;

use Ddeboer\Imap\Server;
use Ddeboer\Imap\Connection;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImapConnectionManager
{
    private static $connections = [];
    private static $cachePrefix = 'imap_connection_';
    
    public static function getConnection(EmailSetting $emailSetting): ?Connection
    {
        $key = self::$cachePrefix . $emailSetting->id;
        
        try {
            if (isset(self::$connections[$key])) {
                return self::$connections[$key];
            }

            // Parse IMAP settings
            $imapSettings = is_string($emailSetting->imap_settings) 
                ? json_decode($emailSetting->imap_settings, true) 
                : $emailSetting->imap_settings;

            // Log full connection details for debugging
            Log::channel('email-sync')->info('Connection details:', [
                'host' => $emailSetting->host,
                'port' => $emailSetting->port,
                'encryption' => $imapSettings['encryption'] ?? 'ssl',
                'validate_cert' => $imapSettings['validate_cert'] ?? false,
                'username' => $emailSetting->username
            ]);

            // Create server connection with proper flags
            $server = new Server(
                $emailSetting->host,
                $emailSetting->port,
                '/ssl/novalidate-cert' // Add proper flags for SSL connection
            );

            // Authenticate with credentials
            $connection = $server->authenticate($emailSetting->username, $emailSetting->password);
            
            // Store connection if successful
            self::$connections[$key] = $connection;
            
            Log::channel('email-sync')->info('IMAP connection successful', [
                'email' => $emailSetting->email,
                'mailboxes' => array_map(
                    fn($mailbox) => $mailbox->getName(),
                    iterator_to_array($connection->getMailboxes())
                )
            ]);

            return $connection;

        } catch (\Exception $e) {
            Log::channel('email-sync')->error('IMAP connection failed:', [
                'error' => $e->getMessage(),
                'email' => $emailSetting->email,
                'host' => $emailSetting->host,
                'port' => $emailSetting->port
            ]);
            return null;
        }
    }

    public static function clearConnection(EmailSetting $emailSetting): void
    {
        $key = self::$cachePrefix . $emailSetting->id;
        if (isset(self::$connections[$key])) {
            try {
                self::$connections[$key]->close();
            } catch (\Exception $e) {
                // Ignore close errors
            }
            unset(self::$connections[$key]);
        }
    }
}
