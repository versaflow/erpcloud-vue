<?php

namespace App\Enums;

enum MessageStatus: string
{
    case READ = 'read';
    case UNREAD = 'unread';

    public function label(): string
    {
        return match($this) {
            self::READ => 'Read',
            self::UNREAD => 'Unread'
        };
    }
}
