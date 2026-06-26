<?php

namespace App\Services;

use App\Events\ApplicationStageChanged;
use App\Models\ApplicationStageHistory;
use App\Models\JobApplication;
use App\Models\WorkflowStage;

class HiringWorkflowEngineService
{
    // ✅ ترتيب الـ type الطبيعي — fallback لو ما في order_index
    private const TYPE_ORDER = [
        'applied'      => 1,
        'screening'    => 2,
        'interview'    => 3,
        'training'     => 4,
        'final_accept' => 5,
        'final_reject' => 5,
    ];

    public function moveToStage(
        int     $applicationId,
        int     $toStageId,
        int     $userId,
        ?string $note = null
    ): array {
        $application = JobApplication::findOrFail($applicationId);
        $toStage     = WorkflowStage::findOrFail($toStageId);
        $fromStageId = $application->current_stage_id;

        // ── تحقق إن الـ stage ينتمي لنفس الـ workflow ──
        if ($application->workflow_id !== $toStage->workflow_id) {
            return $this->error('Stage does not belong to application workflow', 403);
        }

        // ── تحقق إن مش نفس المرحلة ──
        if ($fromStageId === $toStageId) {
            return $this->error('Already in this stage', 409);
        }

        // ── تحقق إن التطبيق مش في final stage ──
        $currentStage = WorkflowStage::find($fromStageId);
        if ($currentStage?->is_final) {
            return $this->error('Application is already in a final stage and cannot be moved', 403);
        }

        // ── تحقق من الترتيب ──
        if (!$this->canMoveToStage($currentStage, $toStage)) {
            return $this->error('Invalid stage transition — cannot move backwards', 403);
        }

        // ── سجّل التاريخ ──
        ApplicationStageHistory::create([
            'job_application_id' => $application->id,
            'from_stage_id'      => $fromStageId,
            'to_stage_id'        => $toStageId,
            'changed_by'         => $userId,
            'note'               => $note,
        ]);

        // ── حدّث المرحلة ──
        $application->update(['current_stage_id' => $toStageId]);

        // ── لو final stage → حدّث الـ status ──
        if ($toStage->is_final) {
            $finalStatus = $toStage->final_status ?? 'rejected';
            $application->update(['status' => $finalStatus]);
        }

        // ── أطلق الـ event ──
        event(new ApplicationStageChanged(
            $application->fresh(),
            $fromStageId,
            $toStageId
        ));

        return $this->success(
            'Moved successfully',
            $application->fresh(['currentStage'])
        );
    }

    /**
     * ✅ تحقق من صحة الانتقال
     * يستخدم order_index لو موجود، وإلا يرجع لـ TYPE_ORDER
     */
    private function canMoveToStage(?WorkflowStage $currentStage, WorkflowStage $toStage): bool
    {
        // أول stage — ما في current
        if (!$currentStage) return true;

        $currentOrder = $this->getStageOrder($currentStage);
        $toOrder      = $this->getStageOrder($toStage);

        // ✅ لازم يكون أكبر من الحالي
        return $toOrder > $currentOrder;
    }

    /**
     * ✅ جلب الترتيب — order_index أولاً، ثم TYPE_ORDER
     */
    private function getStageOrder(WorkflowStage $stage): int
    {
        // لو عنده order_index محدد
        if (isset($stage->order_index) && $stage->order_index > 0) {
            return $stage->order_index;
        }

        // fallback للـ type order
        return self::TYPE_ORDER[$stage->type] ?? 99;
    }

    /**
     * ✅ جلب الـ stages المتاحة للانتقال من المرحلة الحالية
     */
    public function getAvailableStages(int $applicationId): array
    {
        $application  = JobApplication::findOrFail($applicationId);
        $currentStage = WorkflowStage::find($application->current_stage_id);

        if (!$currentStage) {
            // أول stage متاح
            return WorkflowStage::where('workflow_id', $application->workflow_id)
                ->orderBy('order_index')
                ->get()
                ->toArray();
        }

        $currentOrder = $this->getStageOrder($currentStage);

        return WorkflowStage::where('workflow_id', $application->workflow_id)
            ->where(function ($q) use ($currentOrder, $currentStage) {
                // order_index أكبر من الحالي
                $q->where('order_index', '>', $currentOrder)
                    // أو type أعلى في الترتيب
                    ->orWhereIn('type', array_keys(array_filter(
                        self::TYPE_ORDER,
                        fn($o) => $o > (self::TYPE_ORDER[$currentStage->type] ?? 0)
                    )));
            })
            ->orderBy('order_index')
            ->get()
            ->toArray();
    }

    private function success(string $message, $data = null): array
    {
        return [
            'status'  => 200,
            'message' => $message,
            'data'    => $data,
        ];
    }

    private function error(string $message, int $code = 400): array
    {
        return [
            'status'  => $code,
            'message' => $message,
        ];
    }
}
