<?php

namespace App\Http\Controllers;

use App\Http\Requests\WithdrawApplicationRequest;
use App\Models\ApplicationWithdrawal;
use App\Models\JobApplication;
use App\Services\WithdrawApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawApplicationController extends Controller
{
    public function __construct(
        protected WithdrawApplicationService $service
    ) {}

    /**
     * POST /applications/{application}/withdraw
     * سحب طلب وظيفي
     */
    public function withdraw(WithdrawApplicationRequest $request, JobApplication $application): JsonResponse
    {
        $withdrawal = $this->service->withdraw(
            application:     $application,
            userId:          $request->user()->id,
            reasonCategory:  $request->input('reason_category'),
            reasonDetails:   $request->input('reason_details'),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم سحب طلبك بنجاح. تم إشعار الشركة.',
            'data'    => [
                'withdrawal'  => $withdrawal,
                'application' => $application->fresh(),
            ],
        ]);
    }

    /**
     * GET /applications/withdrawals
     * سجل الطلبات المسحوبة للمستخدم
     */
    public function myWithdrawals(Request $request): JsonResponse
    {
        $withdrawals = $this->service->getUserWithdrawals($request->user()->id);

        return response()->json([
            'status' => 'success',
            'data'   => $withdrawals,
        ]);
    }

    /**
     * GET /company/applications/withdrawal-stats
     * إحصائيات الانسحاب (للشركة)
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->service->getWithdrawalStats($request->user()->company->id);

        return response()->json([
            'status' => 'success',
            'data'   => $stats,
        ]);
    }

    /**
     * GET /applications/withdraw/reasons
     * أسباب الانسحاب المتاحة
     */
    public function reasons(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => ApplicationWithdrawal::getReasonCategories(),
        ]);
    }
}
