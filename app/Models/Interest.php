<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    protected $fillable = [
        'user_id',
        'description',
        'level',
        'category',
        'name'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
