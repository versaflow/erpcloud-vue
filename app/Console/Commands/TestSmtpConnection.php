<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailSetting;
use PHPMailer\PHPMailer\PHPMailer;
use App\Services\LoggingService;

class TestSmtpConnection extends Command
{
    protected $signature = 'email:test-smtp {email_setting_id}';
    protected $description = 'Test SMTP connection for a specific email setting';

    protected $logger;

    public function __construct(LoggingService $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    public function handle()
    {
        $settingId = $this->argument('email_setting_id');
        $setting = EmailSetting::with('smtpSetting')->findOrFail($settingId);

        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 3;
        $mail->Timeout = 30;
        // $mail->SMTPTimeout = 30;

        try {
            $smtp = $setting->smtpSetting;
            
            $mail->isSMTP();
            $mail->Host = $smtp->host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp->username;
            $mail->Password = $smtp->password;
            $mail->SMTPSecure = $smtp->encryption;
            $mail->Port = $smtp->port;

            $startTime = microtime(true);
            
            $this->info('Testing connection to ' . $smtp->host . ':' . $smtp->port);
            
            if ($mail->smtpConnect()) {
                $endTime = microtime(true);
                $connectionTime = $endTime - $startTime;
                
                $this->info('Connection successful!');
                $this->info('Connection time: ' . round($connectionTime, 2) . ' seconds');
                
                $mail->smtpClose();
            } else {
                $this->error('Connection failed: ' . $mail->ErrorInfo);
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
