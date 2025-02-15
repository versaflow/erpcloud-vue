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
use HTMLPurifier;
use HTMLPurifier_Config;
use EmailReplyParser\Parser\EmailParser;  // Update this import

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
            // Change all Log:: calls to use email-sync channel
            Log::channel('email-sync')->info('Starting IMAP fetch for: ' . $this->emailSetting->email);
            
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
            Log::channel('email-sync')->info('Full IMAP connection details:', [
                'raw_config' => $config,
                'email_setting' => $this->emailSetting->toArray(),
                'connection_string' => sprintf(
                    '{%s:%d/imap/%s}INBOX',
                    $config['host'],
                    $config['port'],
                    $config['encryption']
                ),
                // 'debug_info' => [
                //     'database_record' => DB::table('email_settings')->where('id', $this->emailSetting->id)->first(),
                //     'raw_imap_settings' => $this->emailSetting->imap_settings
                // ]
            ]);

            $cm = new ClientManager();
            $client = $cm->make($config);

            Log::channel('email-sync')->info('Client configured, attempting connection...');
            
            // Try connecting
            $client->connect();
            
            Log::channel('email-sync')->info('Connection successful!');

            // Get the INBOX folder
            $folder = $client->getFolder('INBOX');

            // Get current time before starting fetch
            $currentTime = now();
            
           
            $lastSync = $this->emailSetting->last_sync_at;
            $searchDate = $lastSync ? $lastSync : now();

            Log::channel('email-sync')->info('Search from ', [
                'from' => $searchDate->format('Y-m-d H:i:s'),
            ]);

            // Get all emails since last sync
            $messages = $folder->query()
                ->since($searchDate)
                ->leaveUnread()
                ->get();


                Log::channel('email-sync')->info('Found messages:', [
                    'count' => $messages->count()
                ]);
            foreach ($messages as $message) {

                $this->processEmail($message);
            }

            // Update last sync time based on results
            if ($messages->count() === 0) {
                // No emails found - use current time
                $this->emailSetting->update(['last_sync_at' => $currentTime]);
                Log::channel('email-sync')->info('No messages found, using current time as last_sync');
            } else {
                // Get last message's date
                $lastMessage = $messages->last();
                Log::channel('email-sync')->info('Last message properties:', [
                    'subject' => $lastMessage->subject,
                    'from' => $lastMessage->from,
                    'date' => $lastMessage->date,
                    'uid' => $lastMessage->uid,
                    'message_id' => $lastMessage->messageId
                ]);
                $lastMessageDate = $lastMessage->date ?? $currentTime;
                
                $this->emailSetting->update(['last_sync_at' => $lastMessageDate]);
                Log::channel('email-sync')->info('Using last message date as last_sync:', [
                    'last_message_date' => $lastMessageDate
                ]);
            }

            Log::channel('email-sync')->info('Completed IMAP fetch');

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Maximum execution time') !== false) {
                Log::channel('email-sync')->warning('Sync timeout - will resume on next run', [
                    'email' => $this->emailSetting->email,
                    'last_sync' => $searchDate->format('Y-m-d H:i:s')
                ]);
                // Update last_sync to the start time of failed batch
                $this->emailSetting->update(['last_sync_at' => $searchDate]);
            }
            Log::channel('email-sync')->error('Fetch failed:', [
                'error' => $e->getMessage(),
                // 'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function findParentConversation($subject, $fromEmail): ?Conversation
    {
        // Remove 'Re:', 'Fwd:', etc and trim
        $cleanSubject = preg_replace('/^(Re|Fwd|Fw):\s*/i', '', $subject);
        $cleanSubject = trim($cleanSubject);
        
        // Find existing conversation with same subject (ignoring Re:) using basic SQL
        return Conversation::where(function($query) use ($cleanSubject, $fromEmail) {
            $query->where(function($q) use ($cleanSubject) {
                // Check for exact match after removing Re:/Fwd:
                $q->where('subject', 'LIKE', $cleanSubject)
                  // Also check if this is the original email that others are replying to
                  ->orWhere('subject', 'LIKE', 'Re: ' . $cleanSubject)
                  ->orWhere('subject', 'LIKE', 'Fwd: ' . $cleanSubject)
                  ->orWhere('subject', 'LIKE', 'Fw: ' . $cleanSubject);
            })->where('from_email', $fromEmail);
        })
        ->latest()
        ->first();
    }

    protected function processEmail($message)
    {
        try {
            DB::beginTransaction();

            $messageId = $message->uid ?? $message->messageId ?? md5($message->subject . $message->from . $message->date);
            
            // Check for existing message using both uid and message_id
            if (Message::where('email_message_id', $messageId)
                ->orWhere('email_message_id', $message->messageId)
                ->exists()) {
                Log::channel('email-sync')->info('Skip duplicate message', [
                    'message_id' => $messageId,
                    'subject' => $message->subject
                ]);
                DB::commit();
                return;
            }

            // Clean sender info - force string conversion for from address
            $fromEmail = $this->cleanEmailAddress((string)(is_array($message->from) ? $message->from[0]->mail : $message->from));
            $fromName = is_array($message->from) ? ($message->from[0]->personal ?? null) : null;
            $fromName = $fromName ?: explode('@', $fromEmail)[0];

            // Create/update support user with force insert
            $supportUser = SupportUser::updateOrCreate(
                ['email' => $fromEmail],
                [
                    'name' => $fromName,
                    'last_seen_at' => now()
                ]
            );

            // Ensure we have a valid support user ID
            if (!$supportUser || !$supportUser->id) {
                throw new \Exception('Failed to create/get support user');
            }

            // Get cleaned content
            $content = $this->getMessageContent($message);
            if (empty($content)) {
                $content = '<p>No content available</p>';
            }

            // Determine if this is a reply
            $isReply = preg_match('/^(Re|Fwd|Fw):/i', (string)$message->subject);
            $parentConversation = null;

            if ($isReply) {
                $parentConversation = $this->findParentConversation($message->subject, $fromEmail);
            }

            if ($parentConversation) {
                // Create reply message with explicit values
                $newMessage = new Message([
                    'content' => $content,
                    'email_message_id' => $messageId,
                    'type' => 'reply',
                    'support_user_id' => $supportUser->id,
                    'conversation_id' => $parentConversation->id
                ]);
                
                $newMessage->save();

                // Update conversation
                $parentConversation->touch();
                $parentConversation->status = ConversationStatus::OPEN;
                $parentConversation->save();

                Log::channel('email-sync')->info('Saved reply to conversation', [
                    'conversation_id' => $parentConversation->id,
                    'message_id' => $newMessage->id
                ]);
            } else {
                // Create new conversation with explicit values
                $conversation = new Conversation([
                    'email_message_id' => $messageId,
                    'subject' => (string)$message->subject ?: 'No Subject',
                    'from_email' => $fromEmail,
                    'to_email' => $this->emailSetting->email,
                    'status' => SpamContact::isSpam($fromEmail) ? ConversationStatus::SPAM : ConversationStatus::NEW,
                    'department_id' => $this->emailSetting->department_id,
                    'support_user_id' => $supportUser->id
                ]);
                
                $conversation->save();

                Log::channel('email-sync')->info('Created new conversation', [
                    'subject' => $conversation->subject,
                    'content' => $content,
                ]);
                
                // Create initial message
                $newMessage = new Message([
                    'content' => $content,
                    'email_message_id' => $messageId,
                    'type' => 'initial',
                    'support_user_id' => $supportUser->id,
                    'conversation_id' => $conversation->id
                ]);
                
                $newMessage->save();

                Log::channel('email-sync')->info('Saved new conversation', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $newMessage->id
                ]);
            }

            // Process attachments if any
            if ($newMessage) {
                $this->processAttachments($message, $newMessage);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('email-sync')->error('Failed to process email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function createNewConversation($message, $messageId, $supportUser, $content, $fromEmail)
    {
        $status = SpamContact::isSpam($fromEmail) ? ConversationStatus::SPAM : ConversationStatus::NEW;
        
        $conversation = Conversation::create([
            'email_message_id' => $messageId,
            'subject' => $message->subject ?? 'No Subject',
            'from_email' => $fromEmail,
            'to_email' => $this->emailSetting->email,
            'status' => $status,
            'department_id' => $this->emailSetting->department_id,
            'support_user_id' => $supportUser->id
        ]);

        $conversation->messages()->create([
            'content' => $content,
            'email_message_id' => $messageId,
            'type' => 'initial',
            'support_user_id' => $supportUser->id
        ]);

        Log::channel('email-sync')->info('Created new conversation', [
            'conversation_id' => $conversation->id,
            'status' => $status
        ]);

        return $conversation;
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
        // Get raw content first
        $rawContent = '';
        
        if (!empty($message->textHtml)) {
            $rawContent = is_array($message->textHtml) ? $message->textHtml[0] : $message->textHtml;
        } elseif (!empty($message->textPlain)) {
            $rawContent = is_array($message->textPlain) ? $message->textPlain[0] : $message->textPlain;
        } elseif (method_exists($message, 'getRawBody')) {
            $rawContent = $message->getRawBody();
        }

        if (empty($rawContent)) {
            return '<p>No content available</p>';
        }

    

        try {
            // Create parser instance with correct method
            $parser = new EmailParser();
            $email = $parser->parse($rawContent);
            
            // Get the most recent fragment (the new content)
            $fragments = $email->getFragments();
            $visibleContent = '';
            
            if (!empty($fragments)) {
                // Get first non-quoted fragment
                foreach ($fragments as $fragment) {
                    if (!$fragment->isQuoted() && !$fragment->isSignature()) {
                        $visibleContent = $fragment->getContent();
                        break;
                    }
                }
            }

            // If no valid fragment found, use original content
            if (empty($visibleContent)) {
                $visibleContent = $rawContent;
            }

            // Convert to HTML if needed
            if (strpos($rawContent, '<html') !== false || strpos($rawContent, '<body') !== false) {
                $content = $this->sanitizeHtml($visibleContent);
            } else {
                $content = nl2br(htmlspecialchars($visibleContent));
            }

            Log::channel('email-sync')->debug('Parsed email content:', [
                'original_length' => strlen($rawContent),
                'parsed_length' => strlen($visibleContent),
                'is_html' => strpos($rawContent, '<html') !== false,
                'fragments_count' => count($fragments ?? [])
            ]);

            return $content;
        } catch (\Exception $e) {
            // Log error but don't fail
            Log::channel('email-sync')->warning('Email parsing failed:', [
                'error' => $e->getMessage()
            ]);
            
            // Fall back to original content
            return $this->sanitizeHtml($rawContent);
        }
    }

    protected function sanitizeHtml($html): string
    {
        // Process with HTML Purifier
        if (class_exists(HTMLPurifier::class)) {
            $config = HTMLPurifier_Config::createDefault();
            
            // Configure allowed elements and attributes
            $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,ul,ol,li,span,div,blockquote,pre,code,hr,h1,h2,h3,h4,h5,h6,table,thead,tbody,tr,td,th,a[href|title],img[src|alt|title|width|height|class]');
            
            // Allow some CSS properties
            $config->set('CSS.AllowedProperties', 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align');
            
            // Set other configuration options
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'tel' => true,
                'data' => true,
            ]);
            
            // Allow data URIs for images (base64 encoded images)
            $config->set('URI.AllowedSchemes', array('http' => true, 'https' => true, 'data' => true));
            $config->set('URI.Base', 'http://www.example.com');
            $config->set('URI.MakeAbsolute', true);
            
            $purifier = new HTMLPurifier($config);
            return $purifier->purify($html);
        }

        // Fallback cleaning if HTMLPurifier isn't available
        $html = strip_tags($html, '<p><br><b><strong><i><em><u><ul><ol><li><span><div><blockquote><pre><code><hr><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><td><th><a><img>');
        
        // Preserve line breaks and spacing
        $html = str_replace(["\n", "\r\n"], '', $html);
        $html = preg_replace('/\s+/', ' ', $html);
        
        return $html;
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

                    Log::channel('email-sync')->info('Attachment saved successfully', [
                        'filename' => $filename,
                        'message_id' => $dbMessage->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('email-sync')->error('Failed to save attachment:', [
                    'error' => $e->getMessage(),
                    'filename' => $filename ?? 'unknown'
                ]);
            }
        }
    }

 
}
