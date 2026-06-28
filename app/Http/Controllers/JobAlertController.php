<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobAlertRequest;
use App\Models\JobAlert;
use App\Services\JobAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobAlertController extends Controller
{
    public function __construct(
        protected JobAlertService $service
    ) {}

    /**
     * GET /job-alerts
     * جلب كل تنبيهات المستخدم
     */
    public function index(Request $request): JsonResponse
    {
        $alerts = JobAlert::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $alerts,
        ]);
    }

    /**
     * POST /job-alerts
     * إنشاء تنبيه جديد
     */
    public function store(JobAlertRequest $request): JsonResponse
    {
        $alert = $this->service->create($request->user(), $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إنشاء التنبيه بنجاح.',
            'data'    => $alert,
        ], 201);
    }

    /**
     * PUT /job-alerts/{alert}
     * تحديث تنبيه
     */
    public function update(JobAlertRequest $request, JobAlert $alert): JsonResponse
    {
        $updated = $this->service->update($alert, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تحديث التنبيه.',
            'data'    => $updated,
        ]);
    }

    /**
     * POST /job-alerts/{alert}/toggle
     * تفعيل/إيقاف تنبيه
     */
    public function toggle(Request $request, JobAlert $alert): JsonResponse
    {
        if ($alert->user_id != $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $updated = $this->service->toggle($alert);

        return response()->json([
            'status'    => 'success',
            'message'   => $updated->is_active ? 'تم تفعيل التنبيه.' : 'تم إيقاف التنبيه.',
            'is_active' => $updated->is_active,
        ]);
    }

    /**
     * DELETE /job-alerts/{alert}
     * حذف تنبيه
     */
    public function destroy(Request $request, JobAlert $alert): JsonResponse
    {
        if ($alert->user_id != $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->service->delete($alert);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حذف التنبيه.',
        ]);
    }
}
