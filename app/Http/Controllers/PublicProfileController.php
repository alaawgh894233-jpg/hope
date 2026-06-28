<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePublicProfileRequest;
use App\Models\PublicProfile;
use App\Services\PublicProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function __construct(
        protected PublicProfileService $service
    ) {}

    /**
     * GET /profile/public
     * جلب إعدادات البروفايل العام للمستخدم
     */
    public function myProfile(Request $request): JsonResponse
    {
        $publicProfile = $this->service->findOrCreate($request->user());

        return response()->json([
            'status' => 'success',
            'data'   => [
                'public_profile' => $publicProfile,
                'public_url'     => $publicProfile->getPublicUrl(),
                'stats'          => $this->service->getStats($publicProfile),
            ],
        ]);
    }

    /**
     * PUT /profile/public
     * تحديث إعدادات البروفايل العام
     */
    public function update(UpdatePublicProfileRequest $request): JsonResponse
    {
        $publicProfile = $this->service->findOrCreate($request->user());
        $updated       = $this->service->updateSettings($publicProfile, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تحديث إعدادات البروفايل.',
            'data'    => $updated,
        ]);
    }

    /**
     * POST /profile/public/change-slug
     * تغيير الرابط المخصص
     */
    public function changeSlug(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string|max:100|regex:/^[a-z0-9\-]+$/',
        ]);

        $publicProfile = $this->service->findOrCreate($request->user());

        try {
            $updated = $this->service->changeSlug($publicProfile, $request->input('slug'));

            return response()->json([
                'status'     => 'success',
                'message'    => 'تم تحديث الرابط بنجاح.',
                'new_url'    => $updated->getPublicUrl(),
                'new_slug'   => $updated->slug,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /p/{slug}
     * عرض البروفايل العام (للزوار)
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $publicProfile = $this->service->getBySlug($slug);

        if (!$publicProfile) {
            return response()->json([
                'status'  => 'error',
                'message' => 'البروفايل غير موجود أو غير متاح.',
            ], 404);
        }

        // تسجيل المشاهدة
        $viewerId = $request->user()?->id;
        $this->service->recordView($publicProfile, $viewerId, $request->ip());

        $profileData = $this->service->buildProfileData($publicProfile);

        return response()->json([
            'status' => 'success',
            'data'   => $profileData,
        ]);
    }
}
