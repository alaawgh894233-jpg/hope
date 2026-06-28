<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'created_by', 'title', 'description',
        'location', 'is_remote', 'salary_range', 'type',
        'status', 'skills', 'tags', 'views',
        'applications_count', 'is_featured', 'expires_at',
    ];

    protected $casts = [
        'is_remote'   => 'boolean',
        'is_featured' => 'boolean',
        'skills'      => 'array',
        'tags'        => 'array',
        'expires_at'  => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }
    public function saves()
    {
        return $this->hasMany(SavedPost::class);
    }
    // ✅ فئات الوظيفة عبر الجدول الوسيط
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'job_post_category');
    }

    public function scopeExcludingBlockedBy($query, ?User $user)
    {
        if (!$user) return $query;
        $blockedCompanyIds = $user->blockedCompanyIds();
        return $query->whereNotIn('company_id', $blockedCompanyIds);
    }
    public function reports() { return $this->morphMany(Report::class, 'reportable'); }
}
