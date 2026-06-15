<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkflowRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'name',
        'field',
        'operator',
        'value',
        'action',
        'target_stage_id',
        'score_weight',
        // 🔥 جديد
        'priority',
        'group_logic', // AND / OR
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
