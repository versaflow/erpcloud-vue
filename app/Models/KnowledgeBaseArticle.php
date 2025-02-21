<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBaseArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'department_id',
        'author_id',
        'status',
        'tags',
        'sent_count'
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function incrementSentCount()
    {
        $this->increment('sent_count');
    }
}
