<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case NEW = 'new';
    case OPEN = 'open';
    case PENDING = 'pending';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case SPAM = 'spam';
    case ASSIGNED = 'assigned';

    public function label(): string
    {
        return match($this) {
            self::NEW => 'New',
            self::OPEN => 'Open',
            self::PENDING => 'Pending',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
            self::SPAM => 'Spam',
            self::ASSIGNED => 'Assigned'
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NEW => 'blue',
            self::OPEN => 'yellow',
            self::PENDING => 'orange',
            self::RESOLVED => 'green',
            self::CLOSED => 'gray',
            self::SPAM => 'red',
            self::ASSIGNED => 'purple'
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
