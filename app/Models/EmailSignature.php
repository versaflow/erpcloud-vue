<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_setting_id', // Add this
        'name',
        'content',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function emailSetting()
    {
        return $this->belongsTo(EmailSetting::class);
    }
}
