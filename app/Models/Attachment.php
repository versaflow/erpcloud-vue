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
}
