<?php

namespace App\Jobs;

use App\Services\ImapConnectionManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
use App\Enums\ConversationStatus;
use DateTimeImmutable;
use App\Events\ConversationsChange;
use Ddeboer\Imap\Message\EmailAddress;

class FetchImapEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailSetting;
    public $timeout = 120;
    public $tries = 1;

    public function __construct(EmailSetting $emailSetting)
    {
        $this->emailSetting = $emailSetting;
    }

    public function handle()
    {
        try {
            Log::channel('email-sync')->info('Starting IMAP fetch for: ' . $this->emailSetting->email);

            $connection = ImapConnectionManager::getConnection($this->emailSetting);
            if (!$connection) {
                throw new \Exception('Could not establish IMAP connection');
            }

            $mailbox = $connection->getMailbox('INBOX');
            $lastSync = $this->emailSetting->last_sync_at ?? now();

            // Use search criteria for better performance
            $searchDate = new DateTimeImmutable($lastSync->format('Y-m-d H:i:s'));
            $messages = $mailbox->getMessages(
                new \Ddeboer\Imap\Search\Date\Since($searchDate)
            );

            $messageCount = count($messages);
            Log::channel('email-sync')->info("Found {$messageCount} messages since {$searchDate->format('Y-m-d H:i:s')}");

            $processed = 0;
            $lastEmailDate = null;
            foreach ($messages as $message) {

                $lastEmailDate = $message->getDate();
                if ($this->processEmail($message)) {
                    $processed++;

                }
            }

            if ($processed != 0) {
                // Broadcast event if any messages were processed
                broadcast(new ConversationsChange('new'))->toOthers();
                Log::channel('email-sync')->info("Broadcasting new conversations event for {$processed} messages");
            }

            Log::channel('email-sync')->info("Processed {$processed} new messages out of {$messageCount} total");

            // Update last sync time using the message date directly
            if ($lastEmailDate) {
                $this->emailSetting->update([
                    'last_sync_at' => $lastEmailDate->format('Y-m-d H:i:s')
                ]);
            }

            Log::channel('email-sync')->info('Email fetch completed successfully');
        } catch (\Exception $e) {
            Log::channel('email-sync')->error('Fetch failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function processEmail($message): bool
    {
        try {
            DB::beginTransaction();

            // Only get basic headers first for duplicate check
            $messageId = $message->getId() ?? $message->getNumber();
            Log::channel('email-sync')->info("Processing message: {$messageId}");
            
            
            // Skip if already processed
            if (Message::where('email_message_id', $messageId)->exists()) {
                Log::channel('email-sync')->info("Skipping already processed message: {$messageId}");
                DB::commit();
                return false;
            }
            Log::channel('email-sync')->info("Processing message: {$messageId}");

            // Now get full message details
            $from = $message->getFrom();
            $fromEmail = $from instanceof EmailAddress ? $from->getAddress() : $from[0]->getAddress();
            $fromName = $from instanceof EmailAddress ? $from->getName() : $from[0]->getName();
            $fromName = $fromName ?: explode('@', $fromEmail)[0];

            // Create/update support user
            $supportUser = SupportUser::updateOrCreate(
                ['email' => $fromEmail],
                ['name' => $fromName, 'last_seen_at' => now()]
            );

            $subject = $message->getSubject() ?? 'No Subject';
            $content = $this->getMessageContent($message);

            // Handle as reply or new conversation
            if (preg_match('/^(Re|Fwd|Fw):/i', $subject)) {
                $this->handleReply($messageId, $message, $supportUser, $content, $fromEmail);
            } else {
                $this->createNewConversation($messageId, $message, $supportUser, $content, $fromEmail);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('email-sync')->error('Failed to process message:', [
                'message_id' => $messageId ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function getMessageContent($message): string
    {
        // Get HTML content
        if ($message->getBodyHtml()) {
            return $message->getBodyHtml();
        }

        // Get text content
        if ($message->getBodyText()) {
            return '<pre style="white-space: pre-wrap;">' .
                htmlspecialchars($message->getBodyText()) .
                '</pre>';
        }

        return '<p>No content available</p>';
    }

    protected function handleReply( $messageId , $message, $supportUser, $content, $fromEmail)
    {
        $subject = $message->getSubject();
        $cleanSubject = preg_replace('/^(Re|Fwd|Fw):\s*/i', '', $subject);

        $conversation = Conversation::where(function ($query) use ($cleanSubject, $fromEmail) {
            $query->where(function ($q) use ($cleanSubject) {
                $q->where('subject', 'LIKE', $cleanSubject)
                    ->orWhere('subject', 'LIKE', 'Re: ' . $cleanSubject)
                    ->orWhere('subject', 'LIKE', 'Fwd: ' . $cleanSubject);
            })->where('from_email', $fromEmail);
        })->latest()->first();

        if (!$conversation) {
            $conversation = $this->createNewConversation($message, $supportUser, $content, $fromEmail);
            return;
        }

        // Create reply message
        $newMessage = Message::create([
            'content' => $content,
            'email_message_id' => $messageId,
            'type' => 'reply',
            'support_user_id' => $supportUser->id,
            'conversation_id' => $conversation->id
        ]);



        // Process attachments for the new message
        $this->processAttachments($messageId, $message, $newMessage);

        // Update conversation status
        $conversation->update([
            'status' => ConversationStatus::OPEN,
            'updated_at' => now()
        ]);
    }

    protected function createNewConversation($messageId, $message, $supportUser, $content, $fromEmail)
    {
        $conversation = Conversation::create([
            'subject' => $message->getSubject() ?: 'No Subject',
            'from_email' => $fromEmail,
            'to_email' => $this->emailSetting->email,
            'status' => SpamContact::isSpam($fromEmail) ? ConversationStatus::SPAM : ConversationStatus::NEW,
            'department_id' => $this->emailSetting->department_id,
            'support_user_id' => $supportUser->id,
            'email_message_id' => $messageId
        ]);

        $newMessage = Message::create([
            'content' => $content,
            'email_message_id' => $messageId,
            'type' => 'initial',
            'support_user_id' => $supportUser->id,
            'conversation_id' => $conversation->id
        ]);



        // Process attachments for the new message
        $this->processAttachments($messageId,$message, $newMessage);

        return $conversation;
    }
    protected function processAttachments($messageId, $emailMessage, $message)
    {
        $attachments = $emailMessage->getAttachments();
        Log::channel('email-sync')->info('Processing attachments:', [
            'message_id' => $messageId,
            'attachment_count' => count($attachments)
        ]);

        foreach ($attachments as $attachment) {
            try {
                $filename = $attachment->getFilename();
                $content = $attachment->getContent();
                $mimeType = $attachment->getType() ?? 'application/octet-stream';
                $size = strlen($content);


                $path = 'attachments/' . date('Y/m');
                $uniquePath = $path . '/' . uniqid() . '_' . $filename;
                
                $storedPath = Storage::put($uniquePath, $content);

              
                if ($storedPath) {
                    $attachmentRecord = $message->attachments()->create([
                        'filename' => $filename,
                        'path' => $uniquePath,
                        'mime_type' => $mimeType,
                        'size' => $size
                    ]);

                
                }
            } catch (\Exception $e) {
                Log::channel('email-sync')->error('Failed to save attachment:', [
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    'filename' => $filename ?? 'unknown',
                    'message_id' => $message->id
                ]);
            }
        }
    }
}
