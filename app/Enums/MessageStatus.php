<?php

namespace App\Enums;

enum MessageStatus: string
{
    case READ = 'read';
    case UNREAD = 'unread';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::READ => 'Read',
            self::UNREAD => 'Unread',
            self::SENT => 'Sent',
            self::FAILED => 'Failed'
        };
    }
}
