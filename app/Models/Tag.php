<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function jobs()
    {
        return $this->belongsToMany(JobPost::class, 'job_post_tag');
    }
}
