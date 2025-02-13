<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;  // Add this import

class MessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'filename',
        'path',
        'mime_type',
        'size'
    ];

    protected $appends = ['url'];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function getUrlAttribute()
    {
        return asset(Storage::url('attachments/'.$this->path));
    }

    public function getSizeFormattedAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    protected static function boot()
    {
        parent::boot();

        // Delete file when attachment is deleted
        static::deleted(function ($attachment) {
            Storage::disk('attachments')->delete($attachment->path);
        });
    }
}