<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportSettings extends Model
{
    protected $fillable = [
        'can_see_all_department_tickets'
    ];

    protected $casts = [
        'can_see_all_department_tickets' => 'boolean'
    ];

    public static function getInstance()
    {
        return static::firstOrCreate([], [
            'can_see_all_department_tickets' => false
        ]);
    }
}
