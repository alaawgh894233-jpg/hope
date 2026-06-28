<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'description',
        'website_url',
        'local_address',
        'phone',
        'logo',
        'support_offers',
        'status',
        'rejection_reason'
    ];
    protected $casts = [
        'support_offers' => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(CompanyDocument::class);
    }
    public function followers()
    {
        return $this->belongsToMany(
            User::class,
            'company_followers',
            'company_id',
            'user_id'
        );
    }
}
