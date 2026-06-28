<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_application_id',
        'user_id',
        'reason_category',
        'reason_details',
        'previous_status',
        'company_notified',
        'company_notified_at',
    ];

    protected $casts = [
        'company_notified'    => 'boolean',
        'company_notified_at' => 'datetime',
    ];

    // ==================
    // Relations
    // ==================

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==================
    // Static Helpers
    // ==================

    public static function getReasonCategories(): array
    {
        return [
            'found_another_job'  => 'وجدت وظيفة أخرى',
            'salary_mismatch'    => 'الراتب لا يناسبني',
            'location_issue'     => 'مشكلة في الموقع الجغرافي',
            'applied_by_mistake' => 'قدّمت بالغلط',
            'changed_mind'       => 'غيّرت رأيي',
            'better_opportunity' => 'فرصة أفضل',
            'personal_reasons'   => 'أسباب شخصية',
            'other'              => 'أخرى',
        ];
    }
}
