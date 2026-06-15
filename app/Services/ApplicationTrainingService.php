<?php

namespace App\Services;

use App\Models\ApplicationTraining;
use App\Models\JobApplication;

class ApplicationTrainingService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function create(array $data)
    {
        return ApplicationTraining::create([
            'job_application_id' => $data['job_application_id'],
            'start_date'         => $data['start_date'],
            'end_date'           => $data['end_date'],
            'notes'              => $data['notes'] ?? null,
            'result'             => 'in_progress'
        ]);
    }

    public function evaluate(
        ApplicationTraining $training,
        int $companyId,
        int $score,
        ?string $notes = null
    ) {
        $application = JobApplication::with('jobPost.company')->findOrFail(
            $training->job_application_id
        );

        if (
            !$application->jobPost ||
            $application->jobPost->company_id != $companyId
        ) {
            abort(403, 'Unauthorized');
        }

        $result = $score >= 60 ? 'passed' : 'failed';

        $training->update([
            'score'  => $score,
            'notes'  => $notes,
            'result' => $result
        ]);

        $application->update([
            'status' => $result === 'passed' ? 'accepted' : 'rejected'
        ]);

        // ✅ إيميل بنتيجة التدريب
        $this->notificationService->notifyTrainingResult($training->fresh());
        $this->notificationService->notifyStatusChanged($application->fresh());

        return $training->fresh();
    }

    public function markPassed(ApplicationTraining $training, int $companyId)
    {
        $application = JobApplication::with('jobPost.company')->findOrFail(
            $training->job_application_id
        );

        if (
            !$application->jobPost ||
            $application->jobPost->company_id != $companyId
        ) {
            abort(403, 'Unauthorized');
        }

        $training->update(['result' => 'passed']);
        $application->update(['status' => 'accepted']);

        $this->notificationService->notifyTrainingResult($training->fresh());
        $this->notificationService->notifyStatusChanged($application->fresh());

        return $training->fresh();
    }

    public function markFailed(ApplicationTraining $training, int $companyId)
    {
        $application = JobApplication::with('jobPost.company')->findOrFail(
            $training->job_application_id
        );

        if (
            !$application->jobPost ||
            $application->jobPost->company_id != $companyId
        ) {
            abort(403, 'Unauthorized');
        }

        $training->update(['result' => 'failed']);
        $application->update(['status' => 'rejected']);

        $this->notificationService->notifyTrainingResult($training->fresh());
        $this->notificationService->notifyStatusChanged($application->fresh());

        return $training->fresh();
    }

    public function complete(ApplicationTraining $training)
    {
        return $training->fresh();
    }
}
