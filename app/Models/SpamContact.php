<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpamContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'value',
        'reason',
        'last_attempt_at',
        'attempt_count'
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime'
    ];

    public function incrementAttempts()
    {
        $this->increment('attempt_count');
        $this->update(['last_attempt_at' => now()]);
    }

    public static function isSpam($email)
    {
        return static::where('type', 'email')
            ->where('value', $email)
            ->exists();
    }

    protected static function boot()
    {
        parent::boot();

        // When a spam contact is deleted, update associated conversations
        static::deleted(function ($spamContact) {
            if ($spamContact->type === 'email') {
                Conversation::where('from_email', $spamContact->value)
                    ->where('status', 'spam')
                    ->update(['status' => 'new']);
            }
        });
    }
}
