<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'issuer',
        'issued_at',
        'expires_at',
        'credential_id'
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
