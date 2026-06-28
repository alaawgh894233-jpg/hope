<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\Review;
use App\Models\ReviewFlag;
use App\Models\ReviewReaction;
use App\Models\ReviewResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    /**
     * إنشاء تقييم
     */
    public function createReview(
        JobApplication $application,
        int $reviewerId,
        string $type,
        array $data
    ): Review {
        $exists = Review::where('job_application_id', $application->id)
            ->where('type', $type)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'review' => 'لقد قمت بتقييم هذا الطلب مسبقاً.',
            ]);
        }

        $this->validateReviewEligibility($application, $reviewerId, $type);

        $revieweeId = $type === 'applicant_to_company'
            ? $application->jobPost->company->user->id
            : $application->user_id;

        $review = Review::create([
            'job_application_id'          => $application->id,
            'reviewer_id'                 => $reviewerId,
            'reviewee_id'                 => $revieweeId,
            'type'                        => $type,
            'overall_rating'              => $data['overall_rating'],
            'work_environment_rating'     => $data['work_environment_rating'] ?? null,
            'management_rating'           => $data['management_rating'] ?? null,
            'salary_benefits_rating'      => $data['salary_benefits_rating'] ?? null,
            'career_growth_rating'        => $data['career_growth_rating'] ?? null,
            'work_life_balance_rating'    => $data['work_life_balance_rating'] ?? null,
            'interview_experience_rating' => $data['interview_experience_rating'] ?? null,
            'technical_skills_rating'     => $data['technical_skills_rating'] ?? null,
            'communication_rating'        => $data['communication_rating'] ?? null,
            'professionalism_rating'      => $data['professionalism_rating'] ?? null,
            'reliability_rating'          => $data['reliability_rating'] ?? null,
            'title'                       => $data['title'] ?? null,
            'pros'                        => $data['pros'] ?? null,
            'cons'                        => $data['cons'] ?? null,
            'advice'                      => $data['advice'] ?? null,
            'would_recommend'             => $data['would_recommend'] ?? null,
            'is_anonymous'                => $data['is_anonymous'] ?? false,
            'status'                      => 'pending',
            'is_completed'                => true,
        ]);

        // 🔔 إشعار الأدمن: تقييم جديد يحتاج موافقة
        $this->notifications->notifyReviewPendingApproval($review);

        return $review;
    }

    /**
     * التحقق من أحقية التقييم
     */
    protected function validateReviewEligibility(
        JobApplication $application,
        int $reviewerId,
        string $type
    ): void {
        if ($type === 'applicant_to_company') {
            if ($application->user_id !== $reviewerId) {
                throw ValidationException::withMessages(['review' => 'غير مصرح.']);
            }

            $allowedStatuses = ['interview', 'training', 'accepted', 'rejected', 'withdrawn'];
            if (! in_array($application->status, $allowedStatuses)) {
                throw ValidationException::withMessages([
                    'review' => 'يمكنك التقييم بعد إجراء المقابلة فقط.',
                ]);
            }
        } else {
            $closedStatuses = ['accepted', 'rejected', 'withdrawn'];
            if (! in_array($application->status, $closedStatuses)) {
                throw ValidationException::withMessages([
                    'review' => 'يمكن تقييم المتقدم فقط بعد إغلاق الطلب.',
                ]);
            }
        }
    }

    /**
     * موافقة الأدمن على تقييم
     */
    public function approveReview(Review $review): void
    {
        $review->update(['status' => 'approved']);

        // 🔔 إشعار صاحب التقييم
        $this->notifications->notifyReviewApproved($review);
    }

    /**
     * رفض تقييم (Admin)
     */
    public function rejectReview(Review $review, string $reason): void
    {
        $review->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // 🔔 إشعار صاحب التقييم
        $this->notifications->notifyReviewRejected($review, $reason);
    }

    /**
     * الإبلاغ عن تقييم — بينشئ سجل ReviewFlag ويحدّث العداد دغري
     */
    public function flagReview(Review $review, int $userId, string $reason): ReviewFlag
    {
        $existing = ReviewFlag::where('review_id', $review->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'flag' => 'لقد قمت بالإبلاغ عن هذا التقييم مسبقاً.',
            ]);
        }

        $flag = DB::transaction(function () use ($review, $userId, $reason) {
            $flag = ReviewFlag::create([
                'review_id' => $review->id,
                'user_id'   => $userId,
                'reason'    => $reason,
                'status'    => 'pending',
            ]);

            $review->increment('flags_count');
            $review->update(['status' => 'flagged']);

            return $flag;
        });

        // 🔔 إشعار الأدمن: في بلاغ جديد
        $this->notifications->notifyReviewFlagged($review, $flag);

        return $flag;
    }

    /**
     * موافقة الأدمن عَ البلاغ = البلاغ صحيح → رفض التقييم نفسه
     */
    public function approveFlag(ReviewFlag $flag, int $adminId, ?string $adminNote = null): ReviewFlag
    {
        return DB::transaction(function () use ($flag, $adminId, $adminNote) {
            $flag->update([
                'status'      => 'resolved',
                'resolved_by' => $adminId,
                'admin_note'  => $adminNote,
                'resolved_at' => now(),
            ]);

            $this->rejectReview($flag->review, $adminNote ?? 'تم رفض التقييم بناءً على بلاغ تم التحقق منه.');

            // 🔔 إشعار للشخص يلي بلّغ
            $this->notifications->notifyFlagApproved($flag);

            return $flag->fresh();
        });
    }

    /**
     * رفض الأدمن للبلاغ = البلاغ غير صحيح → رجوع التقييم لحالته approved
     */
    public function dismissFlag(ReviewFlag $flag, int $adminId, ?string $adminNote = null): ReviewFlag
    {
        return DB::transaction(function () use ($flag, $adminId, $adminNote) {
            $flag->update([
                'status'      => 'dismissed',
                'resolved_by' => $adminId,
                'admin_note'  => $adminNote,
                'resolved_at' => now(),
            ]);

            $review = $flag->review;

            $stillPending = $review->pendingFlags()->where('id', '!=', $flag->id)->exists();
            if (! $stillPending) {
                $review->update(['status' => 'approved']);
            }

            // 🔔 إشعار للشخص يلي بلّغ
            $this->notifications->notifyFlagDismissed($flag);

            return $flag->fresh();
        });
    }

    /**
     * تفاعل مع تقييم (helpful / not_helpful) — بيحدّث العدادات دغري
     */
    public function reactToReview(Review $review, int $userId, string $reaction): array
    {
        return DB::transaction(function () use ($review, $userId, $reaction) {
            $previous = ReviewReaction::where('review_id', $review->id)
                ->where('user_id', $userId)
                ->first();

            ReviewReaction::updateOrCreate(
                ['review_id' => $review->id, 'user_id' => $userId],
                ['reaction'  => $reaction]
            );

            if (! $previous) {
                $review->increment('reactions_count');
                $review->increment($reaction === 'helpful' ? 'helpful_count' : 'not_helpful_count');

                // 🔔 إشعار صاحب التقييم بس عَ أول تفاعل (تجنب سبام)
                if ($review->reviewer_id !== $userId) {
                    $this->notifications->notifyReviewReacted($review, $reaction);
                }
            } elseif ($previous->reaction !== $reaction) {
                $review->decrement($previous->reaction === 'helpful' ? 'helpful_count' : 'not_helpful_count');
                $review->increment($reaction === 'helpful' ? 'helpful_count' : 'not_helpful_count');
            }

            $review->refresh();

            return [
                'helpful_count'     => $review->helpful_count,
                'not_helpful_count' => $review->not_helpful_count,
                'reactions_count'   => $review->reactions_count,
            ];
        });
    }

    /**
     * رد عَ تقييم — بيحدّث العداد دغري
     */
    public function addResponse(Review $review, int $responderId, string $responseText): array
    {
        return DB::transaction(function () use ($review, $responderId, $responseText) {
            $hadResponse = $review->response()->exists();
            $review->response()?->delete();

            $response = ReviewResponse::create([
                'review_id'    => $review->id,
                'responder_id' => $responderId,
                'response'     => $responseText,
            ]);

            if (! $hadResponse) {
                $review->increment('responses_count');
            }

            // 🔔 إشعار صاحب التقييم الأصلي إنو في رد
            $this->notifications->notifyReviewResponded($review);

            return [
                'response'        => $response,
                'responses_count' => $review->fresh()->responses_count,
            ];
        });
    }

    /**
     * جلب تقييمات شركة
     */
    public function getCompanyReviews(int $companyUserId, int $perPage = 10)
    {
        return Review::with(['reviewer', 'jobApplication.jobPost', 'response'])
            ->where('reviewee_id', $companyUserId)
            ->where('type', 'applicant_to_company')
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * إحصائيات تقييمات الشركة
     */
    public function getCompanyRatingStats(int $companyUserId): array
    {
        $reviews = Review::where('reviewee_id', $companyUserId)
            ->where('type', 'applicant_to_company')
            ->approved()
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'average'         => 0,
                'total'           => 0,
                'distribution'    => [],
                'would_recommend' => 0,
                'categories'      => [],
            ];
        }

        return [
            'average'         => round($reviews->avg('overall_rating'), 1),
            'total'           => $reviews->count(),
            'distribution'    => [
                5 => $reviews->where('overall_rating', 5)->count(),
                4 => $reviews->where('overall_rating', 4)->count(),
                3 => $reviews->where('overall_rating', 3)->count(),
                2 => $reviews->where('overall_rating', 2)->count(),
                1 => $reviews->where('overall_rating', 1)->count(),
            ],
            'would_recommend' => $reviews->whereNotNull('would_recommend')
                    ->where('would_recommend', true)->count()
                / max($reviews->whereNotNull('would_recommend')->count(), 1) * 100,
            'categories'      => [
                'work_environment'     => round($reviews->avg('work_environment_rating'), 1),
                'management'           => round($reviews->avg('management_rating'), 1),
                'salary_benefits'      => round($reviews->avg('salary_benefits_rating'), 1),
                'career_growth'        => round($reviews->avg('career_growth_rating'), 1),
                'work_life_balance'    => round($reviews->avg('work_life_balance_rating'), 1),
                'interview_experience' => round($reviews->avg('interview_experience_rating'), 1),
            ],
        ];
    }

    /**
     * جلب التقييمات المُبلّغ عنها (للأدمن)
     */
    public function getFlaggedReviews(int $perPage = 15)
    {
        return Review::with(['reviewer', 'jobApplication.jobPost', 'pendingFlags.user'])
            ->where('status', 'flagged')
            ->whereHas('pendingFlags')
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * جلب بلاغات تقييم معيّن
     */
    public function getReviewFlags(int $reviewId, int $perPage = 15)
    {
        return ReviewFlag::with(['user', 'resolvedBy'])
            ->where('review_id', $reviewId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    public function getUserReviews(int $userId, int $perPage = 10)
    {
        return Review::with([
            'reviewer',
            'jobApplication.jobPost',
            'response'
        ])
            ->where('reviewee_id', $userId)
            ->where('type', 'company_to_applicant')
            ->approved()
            ->latest()
            ->paginate($perPage);
    }
    public function getUserRatingStats(int $userId): array
    {
        $reviews = Review::where('reviewee_id', $userId)
            ->where('type', 'company_to_applicant')
            ->approved()
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'average' => 0,
                'total' => 0,
                'distribution' => [],
                'categories' => [],
            ];
        }

        return [
            'average' => round($reviews->avg('overall_rating'), 1),
            'total' => $reviews->count(),
            'distribution' => [
                5 => $reviews->where('overall_rating', 5)->count(),
                4 => $reviews->where('overall_rating', 4)->count(),
                3 => $reviews->where('overall_rating', 3)->count(),
                2 => $reviews->where('overall_rating', 2)->count(),
                1 => $reviews->where('overall_rating', 1)->count(),
            ],
            'categories' => [
                'technical_skills' => round($reviews->avg('technical_skills_rating'), 1),
                'communication'    => round($reviews->avg('communication_rating'), 1),
                'professionalism'  => round($reviews->avg('professionalism_rating'), 1),
                'reliability'       => round($reviews->avg('reliability_rating'), 1),
            ],
        ];
    }
}
