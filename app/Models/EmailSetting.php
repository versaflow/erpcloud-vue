<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'host',
        'port',
        'username',
        'password',
        'department_id',
        'enabled',
        'imap_settings',
        'last_sync_at'  // Added this
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'imap_settings' => 'array',
        'last_sync_at' => 'datetime'
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function smtpSetting(): HasOne
    {
        return $this->hasOne(SmtpSetting::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(EmailSignature::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'to_email', 'email');
    }

    public function getConnectionConfigAttribute(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->imap_settings['encryption'] ?? 'ssl',
            'validate_cert' => $this->imap_settings['validate_cert'] ?? true,
            'username' => $this->username,
            'password' => $this->password
        ];
    }
}