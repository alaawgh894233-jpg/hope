<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'headline',
        'full_name',
        'summary',
        'gender',
        'phone',
        'address',
        'birth_date',
        'country',
        'city',
        'linkedin',
        'github',
        'portfolio',
        'profile_image',
        'cv_file',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
