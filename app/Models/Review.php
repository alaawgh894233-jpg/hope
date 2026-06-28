<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_application_id',
        'reviewer_id',
        'reviewee_id',
        'type',
        'overall_rating',
        'work_environment_rating',
        'management_rating',
        'salary_benefits_rating',
        'career_growth_rating',
        'work_life_balance_rating',
        'interview_experience_rating',
        'technical_skills_rating',
        'communication_rating',
        'professionalism_rating',
        'reliability_rating',
        'title',
        'pros',
        'cons',
        'advice',
        'would_recommend',
        'is_anonymous',
        'status',
        'rejection_reason',
        'requested_at',
        'is_completed',
    ];

    protected $casts = [
        'would_recommend' => 'boolean',
        'is_anonymous'    => 'boolean',
        'is_completed'    => 'boolean',
        'requested_at'    => 'datetime',
    ];

    // ==================
    // Relations
    // ==================

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
    public function flags()
    {
        return $this->hasMany(ReviewFlag::class);
    }

    public function pendingFlags()
    {
        return $this->hasMany(ReviewFlag::class)->where('status', 'pending');
    }
    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function reactions()
    {
        return $this->hasMany(ReviewReaction::class);
    }

    public function response()
    {
        return $this->hasOne(ReviewResponse::class);
    }

    // ==================
    // Scopes
    // ==================

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeApplicantToCompany($query)
    {
        return $query->where('type', 'applicant_to_company');
    }

    public function scopeCompanyToApplicant($query)
    {
        return $query->where('type', 'company_to_applicant');
    }

    // ==================
    // Helpers
    // ==================

    public function getAverageCompanyRating(): float
    {
        $ratings = array_filter([
            $this->work_environment_rating,
            $this->management_rating,
            $this->salary_benefits_rating,
            $this->career_growth_rating,
            $this->work_life_balance_rating,
            $this->interview_experience_rating,
        ]);

        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;
    }

    public function getAverageApplicantRating(): float
    {
        $ratings = array_filter([
            $this->technical_skills_rating,
            $this->communication_rating,
            $this->professionalism_rating,
            $this->reliability_rating,
        ]);

        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;
    }

    public function getHelpfulCount(): int
    {
        return $this->reactions()->where('reaction', 'helpful')->count();
    }
}
