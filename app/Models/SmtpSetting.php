<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmtpSetting extends Model
{
    protected $fillable = [
        'email_setting_id',
        'from_name',
        'email',
        'host',
        'port',
        'username',
        'password',
        'encryption'
    ];

    public function emailSetting(): BelongsTo
    {
        return $this->belongsTo(EmailSetting::class);
    }
}
