<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateReviewRequest;
use App\Http\Requests\ReviewResponseRequest;
use App\Models\JobApplication;
use App\Models\Review;
use App\Models\ReviewFlag;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $service
    ) {}

    /**
     * POST /applications/{application}/review
     */
    public function store(CreateReviewRequest $request, JobApplication $application): JsonResponse
    {
        $review = $this->service->createReview(
            application: $application,
            reviewerId:  $request->user()->id,
            type:        $request->input('type'),
            data:        $request->validated(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال تقييمك وسيظهر بعد المراجعة.',
            'data'    => $review,
        ], 201);
    }

    /**
     * GET /companies/{companyUserId}/reviews
     */
    public function companyReviews(Request $request, int $companyUserId): JsonResponse
    {
        $reviews = $this->service->getCompanyReviews($companyUserId);
        $stats   = $this->service->getCompanyRatingStats($companyUserId);

        return response()->json([
            'status' => 'success',
            'data'   => ['reviews' => $reviews, 'stats' => $stats],
        ]);
    }

    /**
     * POST /reviews/{review}/respond
     */
    public function respond(ReviewResponseRequest $request, Review $review): JsonResponse
    {
        $user = $request->user();

        if ($review->type === 'applicant_to_company') {
            $company = $review->jobApplication->jobPost->company;

            if (!$company || $company->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'فقط الشركة صاحبة هذا التقييم تستطيع الرد.',
                ], 403);
            }
        } else {
            if ($user->id != $review->jobApplication->user_id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'فقط صاحب التقييم يستطيع الرد.',
                ], 403);
            }
        }

        $result = $this->service->addResponse(
            review: $review,
            responderId: $user->id,
            responseText: $request->input('response'),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إضافة الرد بنجاح.',
            'data'    => [
                'response'        => $result['response'],
                'responses_count' => $result['responses_count'],
            ],
        ], 201);
    }

    /**
     * POST /reviews/{review}/react
     */
    public function react(Request $request, Review $review): JsonResponse
    {
        $request->validate([
            'reaction' => 'required|in:helpful,not_helpful',
        ]);

        $counts = $this->service->reactToReview(
            $review,
            $request->user()->id,
            $request->input('reaction')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل تفاعلك.',
            'data'    => $counts,
        ]);
    }

    /**
     * POST /reviews/{review}/flag
     */
    public function flag(Request $request, Review $review): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $flag = $this->service->flagReview(
            $review,
            $request->user()->id,
            $request->input('reason')
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم الإبلاغ عن التقييم وسيتم مراجعته.',
            'data'    => [
                'flag'        => $flag,
                'flags_count' => $review->fresh()->flags_count,
            ],
        ], 201);
    }

    /**
     * GET /reviews/{review}
     */
    public function show(Review $review): JsonResponse
    {
        if ($review->status !== 'approved') {
            return response()->json(['status' => 'error', 'message' => 'التقييم غير متاح.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $review->load(['reviewer', 'response', 'reactions']),
        ]);
    }

    // ====================== Admin ======================

    /**
     * POST /admin/reviews/{review}/approve
     */
    public function approve(Review $review): JsonResponse
    {
        $this->service->approveReview($review);

        return response()->json([
            'status'  => 'success',
            'message' => 'تمت الموافقة على التقييم.',
            'data'    => $review->fresh(),
        ]);
    }

    /**
     * POST /admin/reviews/{review}/reject
     */
    public function reject(Request $request, Review $review): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $this->service->rejectReview($review, $request->input('reason'));

        return response()->json([
            'status'  => 'success',
            'message' => 'تم رفض التقييم.',
            'data'    => $review->fresh(),
        ]);
    }

    /**
     * GET /admin/reviews/flagged
     */
    public function flaggedReviews(): JsonResponse
    {
        $reviews = $this->service->getFlaggedReviews();

        return response()->json(['status' => 'success', 'data' => $reviews]);
    }

    /**
     * GET /admin/reviews/{review}/flags
     */
    public function reviewFlags(Review $review): JsonResponse
    {
        $flags = $this->service->getReviewFlags($review->id);

        return response()->json(['status' => 'success', 'data' => $flags]);
    }

    /**
     * POST /admin/reviews/flags/{flag}/approve
     */
    public function approveFlag(Request $request, ReviewFlag $flag): JsonResponse
    {
        $request->validate(['admin_note' => 'nullable|string|max:500']);

        $flag = $this->service->approveFlag($flag, $request->user()->id, $request->input('admin_note'));

        return response()->json([
            'status'  => 'success',
            'message' => 'تم قبول البلاغ ورفض التقييم.',
            'data'    => $flag,
        ]);
    }

    /**
     * POST /admin/reviews/flags/{flag}/dismiss
     */
    public function dismissFlag(Request $request, ReviewFlag $flag): JsonResponse
    {
        $request->validate(['admin_note' => 'nullable|string|max:500']);

        $flag = $this->service->dismissFlag($flag, $request->user()->id, $request->input('admin_note'));

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تجاهل البلاغ.',
            'data'    => $flag,
        ]);
    }
    public function userReviews(int $id): JsonResponse
    {
        $reviews = $this->service->getUserReviews($id);
        $stats   = $this->service->getUserRatingStats($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'reviews' => $reviews,
                'stats'   => $stats,
            ],
        ]);
    }
}
