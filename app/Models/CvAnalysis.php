<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvAnalysis extends Model
{
    protected $casts = [
        'cv_snapshot' => 'array',
        'cv_final' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'suggestions' => 'array',
        'ai_insights' => 'array',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'cv_snapshot',
        'cv_final',
        'ats_score',
        'match_score',
        'final_score',
        'job_title',
        'job_description',
        'company',
        'strengths',
        'weaknesses',
        'suggestions',
        'ai_insights',
        'source',
        'model'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
