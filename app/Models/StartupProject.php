<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StartupProject extends Model
{
    use HasFactory;

    protected $table = 'startup_projects';

    protected $fillable = [
        'user_id', 'company_id', 'title', 'description', 'summary',
        'category', 'stage', 'support_types', 'funding_goal',
        'location', 'website_url', 'image', 'status', 'views', 'offers_count',
    ];

    protected $casts = [
        'support_types' => 'array',
        'funding_goal'  => 'decimal:2',
    ];

    public function user()     { return $this->belongsTo(User::class); }
    public function company()  { return $this->belongsTo(Company::class); }
    public function interests(){ return $this->hasMany(StartupProjectInterest::class); }
}
