<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $fillable = [
        'user_id',
        'job_post_id',
        'type'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
