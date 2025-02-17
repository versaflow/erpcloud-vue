<?php

namespace Database\Seeders;

use App\Models\EmailSetting;
use App\Models\SmtpSetting;
use App\Models\EmailSignature;
use Illuminate\Database\Seeder;

class EmailSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $emailSetting = EmailSetting::create([
            'id' => 2,
            'email' => 'itsahaman@fastmail.com',
            'host' => 'imap.fastmail.com',
            'port' => 993,
            'username' => 'itsahaman@fastmail.com',
            'password' => '4n46284d5h8k6g32',
            'department_id' => null,
            'enabled' => true,
            'imap_settings' => [
                'encryption' => 'ssl',
                'validate_cert' => true
            ],
            'last_sync_at' => '2025-02-15 21:35:03'
        ]);

        SmtpSetting::create([
            'email_setting_id' => $emailSetting->id,
            'from_name' => 'itsahaman@fastmail.com',
            'email' => 'itsahaman@fastmail.com',
            'host' => 'smtp.fastmail.com',
            'port' => 587,
            'username' => 'itsahaman@fastmail.com',
            'password' => '4n46284d5h8k6g32',
            'encryption' => 'tls'
        ]);

        EmailSignature::create([
            'email_setting_id' => $emailSetting->id,
            'name' => 'Default',
            'content' => "Best regards,\n{name}\n{position}\n{department}",
            'is_default' => true
        ]);
    }
}
