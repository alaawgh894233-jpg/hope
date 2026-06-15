<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationTraining extends Model
{
    protected $fillable = [
        'job_application_id', 'start_date', 'end_date',
        'notes', 'score', 'result'
    ];
    public function application()
    {
        return $this->belongsTo(JobApplication::class,'job_application_id');
    }
}
