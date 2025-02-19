<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\EmailSetting;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;  // Add this import
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\DB;  // Add this import
use App\Enums\MessageStatus;

class EmailService
{
    protected $logger;

    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    public function sendReply(Conversation $conversation, array $messageData)
    {
        // Set execution time for this specific operation
        set_time_limit(120);
        
        try {
            // Add request data logging
            $this->logger->logInfoEmail('Received message data', [
                'conversation_id' => $conversation->id,
                'cc' => $messageData['cc'] ?? null,
                'bcc' => $messageData['bcc'] ?? null,
            ]);

            // Get the email setting based on the to_email of the conversation
            $emailSetting = EmailSetting::with('smtpSetting')
                ->where('email', $conversation->to_email)
                ->first();

            if (!$emailSetting || !$emailSetting->smtpSetting) {
                throw new \Exception('SMTP configuration not found for ' . $conversation->to_email);
            }

            $smtp = $emailSetting->smtpSetting;

            // Initialize PHPMailer with better timeout settings
            $mail = new PHPMailer(true);
            $mail->SMTPDebug = 0; // Change from 3 to 0 to disable debug output
            $mail->Timeout = config('mail.smtp.timeout', 60);      // Timeout for PHP mail()
            // $mail->SMTPTimeout = config('mail.smtp.timeout', 60);  // Timeout for SMTP connection
            $mail->SMTPKeepAlive = true; // Keep SMTP connection alive
         

            // Log SMTP configuration
            $this->logger->logInfoEmail('Preparing email', [
                'conversation_id' => $conversation->id,
                'smtp_config' => [
                    'from_email' => $smtp->email,
                    'from_name' => $smtp->from_name
                ],
                'recipient' => [
                    'email' => $conversation->from_email,
                    'name' => $conversation->supportUser->name
                ]
            ]);

            // Configure SMTP
            $mail->isSMTP();
            $mail->Host = $smtp->host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp->username;
            $mail->Password = $smtp->password;
            $mail->SMTPSecure = $smtp->encryption;
            $mail->Port = $smtp->port;

            // Add connection error handling
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ],
                'socket' => [
                    'bindto' => '0:0'  // Use any available network interface
                ]
            ];

            // Set sender and recipient info
            $mail->setFrom($smtp->email, $smtp->from_name);
            $mail->addAddress($conversation->from_email, $conversation->supportUser->name);
            $mail->addReplyTo($smtp->email, $smtp->from_name);


            // Handle CC with better validation and logging
            $ccRecipients = [];
            if (!empty($messageData['cc'])) {
                // Log raw CC data
                $this->logger->logInfoEmail('Processing CC recipients', [
                    'cc_raw' => $messageData['cc'],
                    'conversation_id' => $conversation->id
                ]);

                // Split and clean CC addresses
                $ccAddresses = array_filter(array_map('trim', explode(',', $messageData['cc'])));
                
                // Log parsed addresses
                $this->logger->logInfoEmail('Parsed CC addresses', [
                    'addresses' => $ccAddresses,
                    'conversation_id' => $conversation->id
                ]);

                foreach ($ccAddresses as $ccAddress) {
                    if (filter_var($ccAddress, FILTER_VALIDATE_EMAIL)) {
                        $mail->addCC($ccAddress);
                        $ccRecipients[] = $ccAddress;
                        $this->logger->logInfoEmail('Added CC recipient', [
                            'email' => $ccAddress,
                            'conversation_id' => $conversation->id
                        ]);
                    } else {
                        $this->logger->logInfoEmail('Invalid CC email skipped', [
                            'email' => $ccAddress,
                            'conversation_id' => $conversation->id
                        ]);
                    }
                }
            }

            // Handle BCC with better validation and logging
            $bccRecipients = [];
            if (!empty($messageData['bcc'])) {
                $this->logger->logInfoEmail('Processing BCC recipients', [
                    'bcc_raw' => $messageData['bcc']
                ]);

                // Split by comma and handle potential whitespace
                $bccAddresses = array_filter(array_map('trim', explode(',', $messageData['bcc'])));
                foreach ($bccAddresses as $bccAddress) {
                    if (filter_var($bccAddress, FILTER_VALIDATE_EMAIL)) {
                        $mail->addBCC($bccAddress);
                        $bccRecipients[] = $bccAddress;
                        $this->logger->logInfoEmail('Added BCC recipient', [
                            'email' => $bccAddress,
                            'conversation_id' => $conversation->id
                        ]);
                    } else {
                        $this->logger->logInfoEmail('Invalid BCC email skipped', [
                            'email' => $bccAddress,
                            'conversation_id' => $conversation->id
                        ]);
                    }
                }
            }

            // Store CC/BCC in message data for database
            $messageData['cc'] = !empty($ccRecipients) ? implode(',', $ccRecipients) : null;
            $messageData['bcc'] = !empty($bccRecipients) ? implode(',', $bccRecipients) : null;

            // Update recipients log with complete information
            $this->logger->logInfoEmail('Email recipients summary', [
                'conversation_id' => $conversation->id,
                'to' => [$conversation->from_email],
                'cc' => $ccRecipients,
                'bcc' => $bccRecipients
            ]);

            // Set subject with conversation reference
            $mail->Subject = $messageData['subject'] ?? "Re: {$conversation->subject}";

            // Get enabled signature for this email setting
            $signatureContent = $this->getEnabledSignature($emailSetting);
            
            // Combine content with signature if available
            $fullContent = $messageData['content'];
            if ($signatureContent) {
                $fullContent .= $signatureContent;
            }

          

            // Set email content
            $mail->isHTML(true);
            $mail->Body = $fullContent;
            $mail->AltBody = strip_tags($fullContent);

      

            // Add attachments with proper path handling
            if (!empty($messageData['attachments'])) {
                foreach ($messageData['attachments'] as $attachment) {
                    // Verify file exists
                    if (!file_exists($attachment['path'])) {
                        throw new \Exception("Could not access file: {$attachment['path']}");
                    }

                    $mail->addAttachment(
                        $attachment['path'],
                        $attachment['name'],
                        'base64',
                        $attachment['mime_type'] ?? mime_content_type($attachment['path'])
                    );
                }
            }

            // Set message headers
            $mail->addCustomHeader('In-Reply-To', $conversation->email_message_id);
            $mail->addCustomHeader('References', $conversation->email_message_id);
            $mail->addCustomHeader('X-Ticket-ID', $conversation->id);

  

            // Add retry logic for sending
            $maxAttempts = 3;
            $attempt = 1;
            $lastError = null;

            while ($attempt <= $maxAttempts) {
                try {
                    $this->logger->logInfoEmail("Send attempt #{$attempt}", [
                        'conversation_id' => $conversation->id
                    ]);

                    $mail->send();
                    break; // If successful, break the loop
                    
                } catch (\Exception $e) {
                    $lastError = $e;
                    $this->logger->logErrorEmail("Send attempt #{$attempt} failed", [
                        'error' => $e->getMessage(),
                        'conversation_id' => $conversation->id
                    ]);
                    
                    if ($attempt === $maxAttempts) {
                        throw $e; // Rethrow the last error if all attempts fail
                    }
                    
                    sleep(2); // Wait 2 seconds before retrying
                    $attempt++;
                }
            }

            // Log success
            $this->logger->logInfoEmail('Email sent successfully', [
                'conversation_id' => $conversation->id,
                'message_id' => $mail->getLastMessageID(),
                'time' => now()->format('Y-m-d H:i:s.u')
            ]);

            // Create message record
            $message = $this->createMessageRecord($conversation, $messageData, $mail->getLastMessageID());

            // Update conversation status if needed
            if ($conversation->status === 'new') {
                $conversation->update(['status' => 'open']);
            }

            return $message;

        } catch (Exception $e) {
            $this->logger->logErrorEmail('Email sending failed', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
                'smtp_debug' => $mail->ErrorInfo ?? null,
                'stack_trace' => $e->getTraceAsString(),
                'time' => now()->format('Y-m-d H:i:s.u')
            ]);
            throw $e;
        } finally {
            if ($mail->SMTPKeepAlive) {
                $mail->smtpClose();
            }
        }
    }

    private function createMessageRecord($conversation, $messageData, $messageId)
    {
        DB::beginTransaction();
        try {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => Auth::id(),
                'content' => $messageData['content'],
                'type' => 'email',
                'read_at' => now(),
                'email_message_id' => $messageId,
                'status' => MessageStatus::SENT,
                'cc' => $messageData['cc'],
                'bcc' => $messageData['bcc']
            ]);

            // Handle attachments if any
            if (!empty($messageData['attachments'])) {
                foreach ($messageData['attachments'] as $attachment) {
                    $message->attachments()->create([
                        'filename' => $attachment['name'],
                        'path' => $attachment['path'],
                        'mime_type' => mime_content_type($attachment['path']),
                        'size' => filesize($attachment['path'])
                    ]);
                }
            }

            // Update conversation status and timestamp
            $conversation->update([
                'status' => 'open',
                'last_reply_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            
            // Only load attachments if they exist
            return !empty($messageData['attachments']) 
                ? $message->load('attachments') 
                : $message;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->logErrorEmail('Failed to create message record', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function getEnabledSignature($emailSetting)
    {
        // Get only the default signature for this email setting
        $signature = $emailSetting->signatures()
            ->where('is_default', true)
            ->first();

        if (!$signature) {
            return '';
        }

        // Format signature with proper spacing
        return "\n\n" . 
               "-- \n" . 
               trim($signature->content); // trim to remove any extra whitespace
    }


}
