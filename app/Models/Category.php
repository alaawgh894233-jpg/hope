<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name', 'name_ar', 'slug', 'type', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeJobTypes($query)    { return $query->where('type', 'job_type'); }
    public function scopeSectors($query)     { return $query->where('type', 'sector'); }
    public function scopeProjectTypes($query){ return $query->where('type', 'project_type'); }
    public function scopeActive($query)      { return $query->where('is_active', true); }


    public function jobPosts()
    {
        return $this->belongsToMany(JobPost::class, 'job_post_category');
    }
}
