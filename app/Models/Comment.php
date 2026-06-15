<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'job_post_id',
        'content',
        'parent_id'
    ];

    protected $appends = [
        'reactions_count',
        'replies_count'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->with(['user', 'reactions'])
            ->latest();
    }

    public function reactions()
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function getReactionsCountAttribute()
    {
        return $this->reactions()->count();
    }

    public function getRepliesCountAttribute()
    {
        return $this->replies()->count();
    }
}
