<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiringWorkflow extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'is_active'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'workflow_id');
    }


    public function stages()
    {
        return $this->hasMany(
            WorkflowStage::class,
            'workflow_id'
        )->orderBy('order_index');
    }

    public function rules()
    {
        return $this->hasMany(
            WorkflowRule::class,
            'workflow_id'
        );
    }
}
