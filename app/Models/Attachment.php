<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $table = 'message_attachments';
    
    protected $fillable = [
        'message_id',
        'filename',
        'path',
        'mime_type',
        'size'
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function shouldDisplayInline()
    {
        if (!$this->mime_type) {
            return false;
        }

        if (str_starts_with($this->mime_type, 'image/') || $this->mime_type === 'image') {
            return true;
        }

        if (in_array($this->mime_type, ['application/pdf', 'pdf'])) {
            return true;
        }

        return false;
    }
}
