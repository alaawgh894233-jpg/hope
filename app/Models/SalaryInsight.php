<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryInsight extends Model
{
    protected $fillable = [
        'category_id',
        'location',
        'job_type',
        'avg_salary',
        'min_salary',
        'max_salary',
        'sample_size',
        'calculated_at',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'avg_salary'    => 'integer',
        'min_salary'    => 'integer',
        'max_salary'    => 'integer',
        'sample_size'   => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
