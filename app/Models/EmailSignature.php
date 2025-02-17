<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_setting_id',
        'name',
        'content',
        'is_default',
        'is_enabled'  // Add this
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_enabled' => 'boolean'  // Add this
    ];

    public function emailSetting()
    {
        return $this->belongsTo(EmailSetting::class);
    }
}
