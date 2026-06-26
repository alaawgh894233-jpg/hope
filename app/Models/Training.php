<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = [
        'user_id',
        'is_completed',
        'provider',
        'start_date',
        'end_date',
        'title'
    ];

    protected $casts = [
        'start_date' => 'datetime', // ✅ كان date، غيّره لـ datetime
        'end_date' => 'datetime',   // ✅ كان date، غيّره لـ datetime
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
