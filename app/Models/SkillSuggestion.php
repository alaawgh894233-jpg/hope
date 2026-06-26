<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillSuggestion extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'source',
        'reason',
        'job_title',
        'confidence',
        'priority',
        'status',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'confidence'  => 'integer',
        'priority'    => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
