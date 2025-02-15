<?php

namespace App\Services;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImapConnectionManager
{
    private static $connections = [];
    private static $cachePrefix = 'imap_connection_';
    private static $cacheDuration = 3600; // 1 hour
    
    public static function getConnection(EmailSetting $emailSetting): ?Client
    {
        $key = self::$cachePrefix . $emailSetting->id;
        
        // Check if we have a cached connection state
        $hasConnection = Cache::has($key);
        
        Log::channel('email-sync')->info('Checking connection', [
            'email' => $emailSetting->email,
            'exists' => $hasConnection
        ]);

        // If we have a connection in memory, try it first
        if (isset(self::$connections[$key])) {
            try {
                self::$connections[$key]->getFolders();
                Cache::put($key, true, self::$cacheDuration);
                
                Log::channel('email-sync')->info('Reused memory connection', [
                    'email' => $emailSetting->email
                ]);
                return self::$connections[$key];
            } catch (\Exception $e) {
                Log::channel('email-sync')->warning('Memory connection died', [
                    'email' => $emailSetting->email,
                    'error' => $e->getMessage()
                ]);
                unset(self::$connections[$key]);
                Cache::forget($key);
            }
        }
        
        // If we have a cached state but no memory connection, try to recreate
        if ($hasConnection) {
            try {
                $client = self::createConnection($emailSetting);
                if ($client) {
                    Log::channel('email-sync')->info('Recreated cached connection', [
                        'email' => $emailSetting->email
                    ]);
                    return $client;
                }
            } catch (\Exception $e) {
                Log::channel('email-sync')->warning('Failed to recreate cached connection', [
                    'email' => $emailSetting->email,
                    'error' => $e->getMessage()
                ]);
                Cache::forget($key);
            }
        }

        // Create new connection if nothing else worked
        return self::createConnection($emailSetting);
    }

    public static function createConnection(EmailSetting $emailSetting): ?Client
    {
        $key = self::$cachePrefix . $emailSetting->id;
        
        try {
            $config = [
                'host' => $emailSetting->host,
                'port' => $emailSetting->port,
                'encryption' => $emailSetting->imap_settings['encryption'] ?? 'ssl',
                'validate_cert' => $emailSetting->imap_settings['validate_cert'] ?? true,
                'username' => $emailSetting->username,
                'password' => $emailSetting->password,
                'protocol' => 'imap',
                'authentication' => 'plain',
                'options' => [
                    'debug' => false,
                    'auth_type' => 'plain',
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ],
                    'timeout' => -1,
                    'stream_options' => [
                        'tcp' => [
                            'keepalive' => true,
                            'timeout' => -1
                        ]
                    ]
                ]
            ];

            $cm = new ClientManager();
            $client = $cm->make($config);
            $client->connect();
            
            self::$connections[$key] = $client;
            Cache::put($key, true, self::$cacheDuration);
            
            Log::channel('email-sync')->info('Created new connection', [
                'email' => $emailSetting->email
            ]);
            
            return $client;
        } catch (\Exception $e) {
            Log::channel('email-sync')->error('Failed to create connection', [
                'email' => $emailSetting->email,
                'error' => $e->getMessage()
            ]);
            Cache::forget($key);
            return null;
        }
    }

    public static function clearConnection(EmailSetting $emailSetting): void
    {
        $key = self::$cachePrefix . $emailSetting->id;
        unset(self::$connections[$key]);
        Cache::forget($key);
    }

    public static function initializeAllConnections(): void
    {
        EmailSetting::where('active', true)->each(function($setting) {
            $connection = self::getConnection($setting);
            if (!$connection) {
                Log::channel('email-sync')->error('Failed to initialize connection', [
                    'email' => $setting->email
                ]);
            }
        });
    }
}
