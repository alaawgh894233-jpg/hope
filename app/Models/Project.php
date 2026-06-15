<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'link',
        'technologies'
    ];

    protected $casts = [
        'technologies' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
