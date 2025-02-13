<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'content',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];
}
