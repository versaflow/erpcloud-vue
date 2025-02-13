<?php

namespace App\Jobs;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Add this import
use App\Models\EmailSetting;
use App\Models\SupportUser;
use App\Models\SpamContact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Enums\ConversationStatus;  // Add this import

class FetchImapEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailSetting;
    public $timeout = 0; // Never timeout for queue
    public $tries = 1;   // Only try once

    public function __construct(EmailSetting $emailSetting)
    {
        $this->emailSetting = $emailSetting;
        $this->timeout = 120; // Set 2 minute timeout
    }

    public function handle()
    {
        try {
            // Remove PHP execution time limit completely
            set_time_limit(0);
            ini_set('max_execution_time', 0);

            $config = [
                'host'          => $this->emailSetting->host,
                'port'          => $this->emailSetting->port,
                'encryption'    => $this->emailSetting->imap_settings['encryption'] ?? 'ssl',
                'validate_cert' => $this->emailSetting->imap_settings['validate_cert'] ?? true,
                'username'      => $this->emailSetting->username,
                'password'      => $this->emailSetting->password,
                'protocol'      => 'imap',
                'authentication' => 'plain', // Add this line
                'options' => [
                    'debug' => true, // Add debug mode
                    'auth_type' => 'plain',
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ],
                    'timeout' => 0,      // Never timeout IMAP connection
                    'stream_options' => [ // Add stream options
                        'tcp' => [
                            'keepalive' => true,
                            'timeout' => -1
                        ]
                    ]
                ]
            ];

            // Log EVERYTHING for debugging
            Log::info('Full IMAP connection details:', [
                'raw_config' => $config,
                'email_setting' => $this->emailSetting->toArray(),
                'connection_string' => sprintf(
                    '{%s:%d/imap/%s}INBOX',
                    $config['host'],
                    $config['port'],
                    $config['encryption']
                ),
                'debug_info' => [
                    'database_record' => DB::table('email_settings')->where('id', $this->emailSetting->id)->first(),
                    'raw_imap_settings' => $this->emailSetting->imap_settings
                ]
            ]);

            $cm = new ClientManager();
            $client = $cm->make($config);

            Log::info('Client configured, attempting connection...');
            
            // Try connecting
            $client->connect();
            
            Log::info('Connection successful!');

            // Get the INBOX folder
            $folder = $client->getFolder('INBOX');

            // Modify search criteria based on last sync
            $lastSync = $this->emailSetting->last_sync_at;
            $searchDate = $lastSync ? $lastSync : now()->subDay(); // Only fetch last 24h if first sync
            
            Log::info('Search range:', [
                'from' => $searchDate->format('Y-m-d H:i:s'),
                'to' => now()->format('Y-m-d H:i:s')
            ]);

            $messages = $folder->query()
                ->since($searchDate)
                ->limit(100) // Limit to 100 messages per sync
                ->get();

            Log::info('Found messages:', ['count' => $messages->count()]);

            foreach ($messages as $message) {
                $this->processEmail($message);
            }

            $this->emailSetting->update(['last_sync_at' => now()]);
            
            Log::info('Completed IMAP fetch');

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Maximum execution time') !== false) {
                Log::warning('Sync timeout - will resume on next run', [
                    'email' => $this->emailSetting->email,
                    'last_sync' => $this->emailSetting->last_sync_at
                ]);
                // Still update last_sync to prevent re-processing same messages
                $this->emailSetting->update(['last_sync_at' => $searchDate]);
            }
            throw $e;
        }
    }

    protected function processEmail($message)
    {
        DB::beginTransaction();
        try {
            // Debug log incoming message
            Log::info('Processing email:', [
                'subject' => $message->subject,
                'message_id' => $message->uid, // Use UID instead of messageId
                'from' => $message->from,
                'to' => $message->to,
                'has_html' => !empty($message->textHtml),
                'has_plain' => !empty($message->textPlain),
                'size' => $message->size
            ]);

            // Clean up sender email (remove display name)
            $fromEmail = $this->cleanEmailAddress(
                is_array($message->from) ? $message->from[0]->mail : $message->from
            );
            
            $fromName = is_array($message->from) ? ($message->from[0]->personal ?? null) : null;
            $fromName = $fromName ?: explode('@', $fromEmail)[0];

            Log::info('Creating/finding support user:', [
                'email' => $fromEmail,
                'name' => $fromName
            ]);

            // Find or create support user with clean email
            $supportUser = SupportUser::firstOrCreate(
                ['email' => $fromEmail],
                [
                    'name' => $fromName,
                    'last_seen_at' => now()
                ]
            );

            Log::info('Support user:', [
                'id' => $supportUser->id,
                'email' => $supportUser->email
            ]);

            // Use proper message ID for conversation lookup
            $messageId = $message->uid ?? $message->messageId ?? md5($message->subject . $fromEmail . $message->date);

            // Get email content
            $content = $this->getMessageContent($message);
            if (empty($content)) {
                Log::warning('Empty message content!', [
                    'message_id' => $messageId,
                    'subject' => $message->subject
                ]);
            }

            Log::info('Creating conversation:', [
                'message_id' => $messageId,
                'subject' => $message->subject
            ]);

            // Check for spam using enum
            $status = SpamContact::isSpam($fromEmail) 
                ? ConversationStatus::SPAM 
                : ConversationStatus::NEW;

            // If spam, increment counter
            if ($status === ConversationStatus::SPAM) {
                $spamContact = SpamContact::where('type', 'email')
                    ->where('value', $fromEmail)
                    ->first();
                
                if ($spamContact) {
                    $spamContact->incrementAttempts();
                }
            }

            // Create conversation using enum
            $conversation = Conversation::firstOrCreate(
                ['email_message_id' => $messageId],
                [
                    'subject' => $message->subject ?? 'No Subject',
                    'from_email' => $fromEmail,
                    'to_email' => $this->emailSetting->email, // Use IMAP account email
                    'status' => $status,
                    'department_id' => $this->emailSetting->department_id,
                    'support_user_id' => $supportUser->id
                ]
            );

            Log::info('Creating message:', [
                'conversation_id' => $conversation->id,
                'content_length' => strlen($content)
            ]);

            // Create message
            $dbMessage = $conversation->messages()->create([
                'content' => $content,
                'email_message_id' => $messageId,
                'type' => 'initial',
                'support_user_id' => $supportUser->id,
                'user_id' => null
            ]);

            // Process attachments if any
            if ($message->hasAttachments()) {
                $this->processAttachments($message, $dbMessage);
            }

            DB::commit();

            Log::info('Successfully processed email', [
                'message_id' => $messageId,
                'conversation_id' => $conversation->id,
                'message_db_id' => $dbMessage->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Clean email address by removing display name
     */
    protected function cleanEmailAddress($email): string 
    {
        // Remove any display name part (anything before <email>)
        if (preg_match('/<(.+?)>/', $email, $matches)) {
            return $matches[1];
        }
        
        // If no angle brackets, clean up any quotes or extra spaces
        return trim(str_replace(['"', "'"], '', $email));
    }

    protected function getMessageContent($message): string
    {
        // Try HTML first
        if (!empty($message->textHtml)) {
            $content = is_array($message->textHtml) ? $message->textHtml[0] : $message->textHtml;
            return trim($content);
        }

        // Fall back to plain text
        if (!empty($message->textPlain)) {
            $content = is_array($message->textPlain) ? $message->textPlain[0] : $message->textPlain;
            return trim($content);
        }

        // Last resort - try getting raw body
        if (method_exists($message, 'getRawBody')) {
            return trim($message->getRawBody());
        }

        return 'No content available';
    }

    protected function processAttachments($emailMessage, Message $dbMessage)
    {
        if (!$emailMessage->hasAttachments()) {
            return;
        }

        $basePath = storage_path('app/attachments/' . date('Y/m'));
        if (!file_exists($basePath)) {
            mkdir($basePath, 0755, true);
        }

        foreach ($emailMessage->getAttachments() as $attachment) {
            try {
                $filename = $attachment->getName() ?? 'unnamed_file';
                $content = $attachment->getContent();
                $storedPath = $basePath . '/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $filename);

                if (file_put_contents($storedPath, $content)) {
                    $dbMessage->attachments()->create([
                        'filename' => $filename,
                        'path' => str_replace(storage_path('app/'), '', $storedPath),
                        'mime_type' => $attachment->getMimeType() ?? 'application/octet-stream',
                        'size' => strlen($content)
                    ]);

                    Log::info('Attachment saved successfully', [
                        'filename' => $filename,
                        'message_id' => $dbMessage->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to save attachment:', [
                    'error' => $e->getMessage(),
                    'filename' => $filename ?? 'unknown'
                ]);
            }
        }
    }

 
}
