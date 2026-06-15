<?php

namespace App\Services;

use App\Events\ApplicationStageChanged;
use App\Models\ApplicationStageHistory;
use App\Models\JobApplication;
use App\Models\WorkflowStage;

class HiringWorkflowEngineService
{
    public function moveToStage(
        int $applicationId,
        int $toStageId,
        int $userId,
        ?string $note = null
    ) {
        $application = JobApplication::findOrFail($applicationId);
        $toStage     = WorkflowStage::findOrFail($toStageId);
        $fromStageId = $application->current_stage_id;

        if ($application->workflow_id !== $toStage->workflow_id) {
            return $this->error('Stage does not belong to application workflow', 403);
        }

        if ($fromStageId === $toStageId) {
            return $this->error('Already in this stage', 409);
        }

        // ✅ تحقق حقيقي من الترتيب — ما نقدر نرجع لوراء
        if (!$this->canMoveToStage($application, $toStage)) {
            return $this->error('Invalid stage transition', 403);
        }

        ApplicationStageHistory::create([
            'job_application_id' => $application->id,
            'from_stage_id'      => $fromStageId,
            'to_stage_id'        => $toStageId,
            'changed_by'         => $userId,
            'note'               => $note
        ]);

        $application->update(['current_stage_id' => $toStageId]);

        // ✅ is_final مع تفريق بين accepted وrejected حسب نوع الـ stage
        if ($toStage->is_final) {
            $finalStatus = $toStage->final_status ?? 'rejected'; // ✅ الـ stage بيحدد النتيجة
            $application->update(['status' => $finalStatus]);
        }

        event(new ApplicationStageChanged(
            $application,
            $fromStageId,
            $toStageId
        ));

        return $this->success('Moved successfully', $application->fresh('currentStage'));
    }

    // ✅ منطق حقيقي — ما نقدر نرجع لـ stage رقمه أصغر من الحالي
    private function canMoveToStage($application, $toStage): bool
    {
        $currentStage = WorkflowStage::find($application->current_stage_id);

        if (!$currentStage) {
            return true; // أول stage
        }

        // ✅ الـ order_index لازم يكون أكبر من الحالي (مش نرجع لوراء)
        return $toStage->order_index > $currentStage->order_index;
    }

    private function success($message, $data = null): array
    {
        return [
            'status'  => 200,
            'message' => $message,
            'data'    => $data
        ];
    }

    private function error($message, $code = 400): array
    {
        return [
            'status'  => $code,
            'message' => $message
        ];
    }
}
