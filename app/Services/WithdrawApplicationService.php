<?php

namespace App\Services;

use App\Models\ApplicationWithdrawal;
use App\Models\JobApplication;
use App\Notifications\ApplicationWithdrawnNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawApplicationService
{
    // الحالات التي لا يمكن سحب الطلب منها
    protected array $nonWithdrawableStatuses = [
        'hired',      // تم التوظيف
        'rejected',   // مرفوض أصلاً
        'withdrawn',  // مسحوب أصلاً
    ];

    /**
     * سحب طلب وظيفي
     */
    public function withdraw(
        JobApplication $application,
        int $userId,
        string $reasonCategory,
        ?string $reasonDetails = null
    ): ApplicationWithdrawal {

        // التحقق من الصلاحية
        $this->validateWithdrawal($application, $userId);

        return DB::transaction(function () use ($application, $userId, $reasonCategory, $reasonDetails) {

            $previousStatus = $application->status;

            // تحديث حالة الطلب
            $application->update([
                'status'          => 'withdrawn',
                'withdraw_reason' => $reasonDetails,
                'withdrawn_at'    => now(),
            ]);

            // حفظ سجل الانسحاب
            $withdrawal = ApplicationWithdrawal::create([
                'job_application_id' => $application->id,
                'user_id'            => $userId,
                'reason_category'    => $reasonCategory,
                'reason_details'     => $reasonDetails,
                'previous_status'    => $previousStatus,
                'company_notified'   => false,
            ]);

            // إشعار الشركة
            $this->notifyCompany($application, $withdrawal);

            return $withdrawal;
        });
    }

    /**
     * التحقق من إمكانية سحب الطلب
     */
    protected function validateWithdrawal(JobApplication $application, int $userId): void
    {
        // التحقق من ملكية الطلب
        if ($application->user_id !== $userId) {
            throw ValidationException::withMessages([
                'application' => 'ليس لديك صلاحية لسحب هذا الطلب.',
            ]);
        }

        // التحقق من الحالة
        if (in_array($application->status, $this->nonWithdrawableStatuses)) {
            $statusMessages = [
                'hired'     => 'لا يمكن سحب الطلب بعد قبولك للتوظيف.',
                'rejected'  => 'لا يمكن سحب طلب تم رفضه مسبقاً.',
                'withdrawn' => 'تم سحب هذا الطلب مسبقاً.',
            ];

            throw ValidationException::withMessages([
                'status' => $statusMessages[$application->status] ?? 'لا يمكن سحب هذا الطلب في وضعه الحالي.',
            ]);
        }
    }

    /**
     * إشعار الشركة بسحب الطلب
     */
    protected function notifyCompany(JobApplication $application, ApplicationWithdrawal $withdrawal): void
    {
        try {
            $companyUser = $application->jobPost->company->user;

            if (!$companyUser) {
                logger()->error('Company user not found for withdrawal notification');
                return;
            }

            $companyUser->notify(
                new ApplicationWithdrawnNotification($application, $withdrawal)
            );

            $withdrawal->update([
                'company_notified'    => true,
                'company_notified_at' => now(),
            ]);

        } catch (\Exception $e) {
            logger()->error('Withdrawal notification failed: ' . $e->getMessage());
        }
    }

    /**
     * جلب سجلات الانسحاب للمستخدم
     */
    public function getUserWithdrawals(int $userId)
    {
        return ApplicationWithdrawal::with(['jobApplication.jobPost'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * إحصائيات الانسحاب (للشركة)
     */
    public function getWithdrawalStats(int $companyId): array
    {
        $withdrawals = ApplicationWithdrawal::whereHas('jobApplication.jobPost', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();

        return [
            'total'           => $withdrawals->count(),
            'by_reason'       => $withdrawals->groupBy('reason_category')->map->count(),
            'this_month'      => $withdrawals->where('created_at', '>=', now()->startOfMonth())->count(),
            'rate_percentage' => $this->calculateWithdrawalRate($companyId),
        ];
    }

    private function calculateWithdrawalRate(int $companyId): float
    {
        // معدل الانسحاب = (عدد المنسحبين / إجمالي الطلبات) * 100
        $total = JobApplication::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))->count();

        if ($total === 0) return 0;

        $withdrawn = JobApplication::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'withdrawn')
            ->count();

        return round(($withdrawn / $total) * 100, 1);
    }
}
