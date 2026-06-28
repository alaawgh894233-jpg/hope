<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'criteria',
        'frequency',
        'notify_email',
        'notify_push',
        'notify_sms',
        'is_active',
        'last_sent_at',
        'total_sent',
    ];

    protected $casts = [
        'criteria'      => 'array',
        'notify_email'  => 'boolean',
        'notify_push'   => 'boolean',
        'notify_sms'    => 'boolean',
        'is_active'     => 'boolean',
        'last_sent_at'  => 'datetime',
    ];

    // ==================
    // Relations
    // ==================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==================
    // Scopes
    // ==================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInstant($query)
    {
        return $query->where('frequency', 'instantly');
    }

    public function scopeDaily($query)
    {
        return $query->where('frequency', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('frequency', 'weekly');
    }

    // ==================
    // Helpers
    // ==================

    /**
     * Check if a job post matches this alert's criteria
     */
    public function matchesJob(array $jobData): bool
    {
        $criteria = $this->criteria;

        // فحص الكلمات المفتاحية
        if (!empty($criteria['keywords'])) {
            $matched = false;
            foreach ($criteria['keywords'] as $keyword) {
                if (
                    str_contains(strtolower($jobData['title'] ?? ''), strtolower($keyword)) ||
                    str_contains(strtolower($jobData['description'] ?? ''), strtolower($keyword))
                ) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) return false;
        }

        // فحص الموقع
        if (!empty($criteria['location']) && isset($jobData['location'])) {
            if (!str_contains(strtolower($jobData['location']), strtolower($criteria['location']))) {
                return false;
            }
        }

        // فحص نوع الوظيفة
        if (!empty($criteria['job_type']) && isset($jobData['job_type'])) {
            if (!in_array($jobData['job_type'], $criteria['job_type'])) {
                return false;
            }
        }

        // فحص الراتب
        if (!empty($criteria['salary_min']) && isset($jobData['salary_min'])) {
            if ($jobData['salary_min'] < $criteria['salary_min']) {
                return false;
            }
        }

        return true;
    }
}
