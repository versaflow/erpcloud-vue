<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;

class ConversationStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function broadcastOn()
    {
        Log::info('Broadcasting ConversationStatusChanged event', [
            'conversation_id' => $this->conversation->id,
            'status' => $this->conversation->status,
            'channel' => 'helpdesk',
            'timestamp' => now()->toIso8601String()
        ]);
        
        return new Channel('helpdesk');
    }

    public function broadcastWith()
    {
        $data = [
            'conversation' => [
                'id' => $this->conversation->id,
                'status' => $this->conversation->status,
                'subject' => $this->conversation->subject,
                'updated_at' => $this->conversation->updated_at,
                'timestamp' => now()->toIso8601String()
            ]
        ];

        Log::info('Broadcasting payload', $data);
        
        return $data;
    }
}
