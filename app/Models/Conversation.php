<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'subject',
        'email_message_id',
        'support_user_id',
        'department_id',
        'agent_id',
        'assigned_at',
        'status',
        'from_email',
        'to_email',
        'source',
        'source_id'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'status' => ConversationStatus::class  // Cast status to enum
    ];

    // Add agent relationship
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    // Add messages relationship
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function supportUser()
    {
        return $this->belongsTo(SupportUser::class);
    }

    // Add department relationship
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Remove the attachments() method as it's now linked to Message model
    
    // Add a method to get all attachments through messages
    public function getAllAttachments()
    {
        return Attachment::whereIn('message_id', $this->messages()->pluck('id'));
    }

    // Helper method to assign agent
    public function assignTo(User $agent)
    {
        $this->update([
            'agent_id' => $agent->id,
            'assigned_at' => now(),
            'status' => $this->status === 'new' ? 'assigned' : $this->status
        ]);
    }

    // Helper method to unassign
    public function unassign()
    {
        $this->update([
            'agent_id' => null,
            'assigned_at' => null,
            'status' => 'open'
        ]);
    }

    // Add helper method for source
    public function setSource(string $source, ?string $sourceId = null)
    {
        $this->source = $source;
        $this->source_id = $sourceId;
        return $this;
    }

    // Helper methods using enum
    public function markAsSpam()
    {
        $this->status = ConversationStatus::SPAM;
        $this->save();
    }

    public function markAsNew()
    {
        $this->status = ConversationStatus::NEW;
        $this->save();
    }

    public function isSpam(): bool
    {
        return $this->status === ConversationStatus::SPAM;
    }
}
