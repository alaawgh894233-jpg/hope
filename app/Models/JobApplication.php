<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_post_id',
        'user_id',
        'cover_letter',
        'cv_snapshot',
        'status',
        'cv_file',
    ];

    protected $casts = [
        'cv_snapshot' => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

   public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }
    public function workflow()
    {
        return $this->belongsTo(HiringWorkflow::class);
    }

    public function currentStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }

    public function stageHistory()
    {
        return $this->hasMany(ApplicationStageHistory::class);
    }
    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function trainings()
    {
        return $this->hasMany(ApplicationTraining::class);
    }
}
