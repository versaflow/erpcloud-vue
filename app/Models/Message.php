<?php

namespace App\Models;

use App\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'content',
        'email_message_id',
        'support_user_id',
        'user_id',
        'type',
        'direction',
        'from_name',
        'from_email',
        'to_email',
        'cc',
        'quoted_text',
        'signature',
        'status',
        'read_at'
    ];

    protected $with = ['attachments'];

    protected $casts = [
        'status' => MessageStatus::class,
        'read_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => MessageStatus::UNREAD
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function supportUser()
    {
        return $this->belongsTo(SupportUser::class);
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function getHasAttachmentsAttribute()
    {
        return $this->attachments()->exists();
    }

    public function scopeInitial($query)
    {
        return $query->where('type', 'initial');
    }

    public function scopeReplies($query)
    {
        return $query->where('type', 'reply');
    }

    public function getSenderAttribute()
    {
        if ($this->support_user_id) {
            return $this->supportUser;
        }
        return $this->user;
    }

    public function getSenderTypeAttribute()
    {
        return $this->support_user_id ? 'customer' : 'agent';
    }

    public function markAsRead()
    {
        $this->update([
            'status' => MessageStatus::READ,
            'read_at' => now()
        ]);
    }

    public function markAsUnread()
    {
        $this->update([
            'status' => MessageStatus::UNREAD,
            'read_at' => null
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('status', MessageStatus::UNREAD);
    }

    public function scopeRead($query)
    {
        return $query->where('status', MessageStatus::READ);
    }

    protected static function boot()
    {
        parent::boot();

        // Update conversation's updated_at when new message is added
        static::created(function ($message) {
            $message->conversation->touch();
        });

        static::saving(function ($message) {
            // Cleanup content if needed
            if (strlen($message->content) > 16777215) { // MySQL MEDIUMTEXT limit
                $message->content = substr($message->content, 0, 16777215);
            }
        });
    }
}
