<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $fillable = [
        'user_id',
        'company',
        'position',
        'start_date',
        'end_date',
        'is_current',
        'description',
        'technologies_used'
    ];

    protected $casts = [
        'start_date' => 'datetime', // ✅ مهم
        'end_date' => 'datetime',   // ✅ مهم
        'is_current' => 'boolean',
        'technologies_used' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
