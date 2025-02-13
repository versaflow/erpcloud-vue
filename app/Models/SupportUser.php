<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupportUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'location',
        'timezone',
        'notes',
        'tags',
        'last_seen_at',
        'last_contacted_at'
    ];

    protected $casts = [
        'tags' => 'array',
        'last_seen_at' => 'datetime',
        'last_contacted_at' => 'datetime'
    ];

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getInitialsAttribute()
    {
        return collect(explode(' ', $this->name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->join('');
    }

    public function scopeActive($query)
    {
        return $query->where('last_seen_at', '>', now()->subDays(30));
    }

    public function updateLastSeen()
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function updateLastContacted()
    {
        $this->update(['last_contacted_at' => now()]);
    }

    public function addTag($tag)
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag($tag)
    {
        $tags = $this->tags ?? [];
        $this->update(['tags' => array_diff($tags, [$tag])]);
    }
}
