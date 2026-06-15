<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationStageHistory extends Model
{
    protected $table = 'application_stage_history';

    protected $fillable = [
        'job_application_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by',
        'note'
    ];

    public function application()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function fromStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'from_stage_id');
    }

    public function toStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'to_stage_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
