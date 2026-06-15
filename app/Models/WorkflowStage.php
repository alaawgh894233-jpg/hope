<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStage extends Model
{
    protected $fillable = [
        'workflow_id',
        'name',
        'order_index',
        'requires_approval',
        'is_final'
    ];

    public function workflow()
    {
        return $this->belongsTo(HiringWorkflow::class, 'workflow_id');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'current_stage_id');
    }

}
