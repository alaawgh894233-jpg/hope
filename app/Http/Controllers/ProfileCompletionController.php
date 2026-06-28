<?php

namespace App\Http\Controllers;

use App\Services\ProfileCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileCompletionController extends Controller
{
    public function __construct(
        protected ProfileCompletionService $service
    ) {}

    /**
     * GET /profile/completion
     * جلب نسبة اكتمال البروفايل
     */
    public function index(Request $request): JsonResponse
    {
        $user       = $request->user()->load(['profile', 'experiences', 'educations', 'skills', 'certifications']);
        $completion = $this->service->calculate($user);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'percentage'      => $completion->percentage,
                'level'           => $completion->getLevel(),
                'level_label'     => $completion->getLevelLabel(),
                'sections'        => $completion->sections,
                'missing'         => $completion->getMissingSections(),
                'recommendations' => $this->service->getRecommendations($user),
                'has_basic_info'  => $completion->has_basic_info,
                'has_photo'       => $completion->has_photo,
                'has_summary'     => $completion->has_summary,
                'has_experience'  => $completion->has_experience,
                'has_education'   => $completion->has_education,
                'has_skills'      => $completion->has_skills,
                'has_cv_file'     => $completion->has_cv_file,
                'has_certifications' => $completion->has_certifications,
            ],
        ]);
    }

    /**
     * POST /profile/completion/recalculate
     * إعادة حساب النسبة (بعد تحديث البروفايل)
     */
    public function recalculate(Request $request): JsonResponse
    {
        $user       = $request->user()->load(['profile', 'experiences', 'educations', 'skills', 'certifications']);
        $completion = $this->service->calculate($user);

        return response()->json([
            'status'     => 'success',
            'message'    => 'تم تحديث نسبة الاكتمال.',
            'percentage' => $completion->percentage,
        ]);
    }
}
