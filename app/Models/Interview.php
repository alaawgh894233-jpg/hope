<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $fillable = [
        'job_application_id',
        'scheduled_at',
        'type',
        'location',
        'meeting_link',
        'status',
        'notes',
        'feedback',
        'score'
    ];
    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }
}
