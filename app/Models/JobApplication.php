<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_post_id',
        'user_id',
        'cover_letter',
        'cv_snapshot',
        'status',
        'cv_file',
        // حقول الانسحاب الجديدة
        'withdraw_reason',
        'withdrawn_at',
        'can_reapply',
    ];

    protected $casts = [
        'cv_snapshot'  => 'array',
        'withdrawn_at' => 'datetime',
        'can_reapply'  => 'boolean',
    ];

    // ==================
    // Constants
    // ==================

    const STATUS_PENDING    = 'pending';
    const STATUS_REVIEWING  = 'reviewing';
    const STATUS_SHORTLISTED= 'shortlisted';
    const STATUS_INTERVIEWED= 'interviewed';
    const STATUS_OFFERED    = 'offered';
    const STATUS_HIRED      = 'hired';
    const STATUS_REJECTED   = 'rejected';
    const STATUS_WITHDRAWN  = 'withdrawn';

    // ==================
    // Relations
    // ==================

    public function workflow()
    {
        return $this->belongsTo(HiringWorkflow::class);
    }

    public function currentStage()
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }

    public function stageHistory()
    {
        return $this->hasMany(ApplicationStageHistory::class)
            ->orderBy('created_at', 'desc');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function trainings()
    {
        return $this->hasMany(ApplicationTraining::class);
    }

    // ============================
    // العلاقات الجديدة
    // ============================

    /** محادثة الـ Chat بعد القبول */
    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    /** سجل الانسحاب */
    public function withdrawal()
    {
        return $this->hasOne(ApplicationWithdrawal::class);
    }

    /** التقييمات المرتبطة بالطلب */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /** تقييم المتقدم للشركة */
    public function applicantReview()
    {
        return $this->hasOne(Review::class)->where('type', 'applicant_to_company');
    }

    /** تقييم الشركة للمتقدم */
    public function companyReview()
    {
        return $this->hasOne(Review::class)->where('type', 'company_to_applicant');
    }

    // ==================
    // Scopes
    // ==================

    public function scopeWithdrawn($query)
    {
        return $query->where('status', self::STATUS_WITHDRAWN);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_REJECTED, self::STATUS_WITHDRAWN]);
    }

    // ==================
    // Helpers
    // ==================

    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }

    public function canBeWithdrawn(): bool
    {
        return !in_array($this->status, ['hired', 'rejected', 'withdrawn']);
    }

    public function isHired(): bool
    {
        return $this->status === self::STATUS_HIRED;
    }

    public function canOpenChat(): bool
    {
        return in_array($this->status, ['offered', 'hired']);
    }

    public function canReview(): bool
    {
        return in_array($this->status, ['interviewed', 'offered', 'hired', 'rejected']);
    }
}
