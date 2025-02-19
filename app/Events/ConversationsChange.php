<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Conversation;
use App\Models\Message;

class ConversationsChange implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action;
    public ?Conversation $conversation;
    public ?Message $message;

    public function __construct(string $action, ?Conversation $conversation = null, ?Message $message = null)
    {
        $this->action = $action;
        $this->conversation = $conversation;
        $this->message = $message;

    }
    public function broadcastWith()
    {
        $data = [
            'action' => $this->action,
            'timestamp' => now()->toIso8601String()
        ];

        if ($this->conversation) {
            switch ($this->action) {
                case 'new_message':
                    $data['message'] = $this->message;
                    $data['conversation'] = ['id' => $this->conversation->id];
                    break;
                
                case 'department_change':
                    $data['conversation'] = [
                        'id' => $this->conversation->id,
                        'department_id' => $this->conversation->department_id,
                        'department' => $this->conversation->department
                    ];
                    break;
                    
                case 'agent_change':
                    $data['conversation'] = [
                        'id' => $this->conversation->id,
                        'agent_id' => $this->conversation->agent_id,
                        'agent' => $this->conversation->agent
                    ];
                    break;
                    
                case 'spam':
                case 'unspam':
                    $data['conversation'] = [
                        'id' => $this->conversation->id,
                        'status' => $this->conversation->status
                    ];
                    break;
                    
                default:
                    $data['conversation'] = [
                        'id' => $this->conversation->id,
                        'status' => $this->conversation->status,
                        'updated_at' => $this->conversation->updated_at
                    ];
            }
        }

        return $data;
    }

    public function broadcastOn()
    {
        return new Channel('helpdesk');
    }
}
