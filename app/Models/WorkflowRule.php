<?php
// app/Models/WorkflowRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRule extends Model
{
    protected $fillable = [
        'workflow_id',
        'name',
        'field',
        'operator',
        'value',
        'action',
        'score_weight',
        'priority',
        'group_logic',
        'target_stage_id',
    ];

    public function workflow()
    {
        return $this->belongsTo(HiringWorkflow::class);
    }

    public function targetStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'target_stage_id');
    }
}
