<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'level',
        'years_experience'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
