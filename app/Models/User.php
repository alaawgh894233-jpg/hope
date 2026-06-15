<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory,Notifiable , HasRoles;
//    protected string $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
       'banned_at',
        'ban_reason',
        'deleted_at'
    ];

    protected $hidden = [
        'password',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function educations()
    {
        return $this->hasMany(Education::class);
    }

    public function skillSuggestions()
    {
        return $this->hasMany(
            SkillSuggestion::class
        );
    }
    public function cvAnalysis()
    {
        return $this->hasOne(CvAnalysis::class);
    }
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    public function followedCompanies()
    {
        return $this->belongsToMany(
            Company::class,
            'company_followers'
        );
    }public function followers()
{
    return $this->belongsToMany(
        User::class,
        'company_followers'
    );
}
    public function interests()
    {
        return $this->hasMany(Interest::class);
    }
    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function savedPosts()
    {
        return $this->belongsToMany(
            JobPost::class,
            'saved_posts'
        );
    }
    public function company()
    {
        return $this->hasOne(Company::class);
    }
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }
    public function certifications()
    {
        return $this->hasMany(Certification::class);
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',

        ];
    }
}
