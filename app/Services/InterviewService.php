<?php

namespace App\Services;

use App\Models\ApplicationTraining;
use App\Models\Interview;
use App\Models\JobApplication;

class InterviewService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly  ConversationService $conversationService
    ) {}

    public function schedule(array $data, $user)
    {
        $application = JobApplication::with('jobPost')->findOrFail(
            $data['job_application_id']
        );

        if (
            $user->role !== 'admin' &&
            $application->jobPost?->company_id !== $user->company?->id
        ) {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        // ✅ كان 'pending' غلط — صار 'scheduled'
        $alreadyScheduled = Interview::where('job_application_id', $application->id)
            ->where('status', 'scheduled')
            ->exists();

        if ($alreadyScheduled) {
            return ['status' => 409, 'message' => 'Interview already scheduled'];
        }

        $application->update(['status' => 'interview']);

        $interview = Interview::create([
            'job_application_id' => $application->id,
            'scheduled_at'       => $data['scheduled_at'],
            'type'               => $data['type'],
            'location'           => $data['location'] ?? null,
            'meeting_link'       => $data['meeting_link'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'status'             => 'scheduled',  // ✅ أضفناه
        ]);

        $this->conversationService->findOrCreateForApplication(
            $application,
            $user->id
        );
        $this->notificationService->notifyInterviewScheduled(
            $interview->load('jobApplication.jobPost')
        );

        return ['status' => 200, 'data' => $interview];
    }

    public function complete(Interview $interview, bool $passed, $user, ?string $feedback = null)
    {
        $application = JobApplication::with('jobPost.company')->findOrFail(
            $interview->job_application_id
        );

        if (
            $user->role !== 'admin' &&
            (!$application->jobPost || $application->jobPost->company_id != $user->company?->id)
        ) {
            abort(403, 'Unauthorized');
        }

        $interview->update([
            'status'   => $passed ? 'passed' : 'failed',
            'feedback' => $feedback,
        ]);

        if ($passed) {
            $application->update(['status' => 'training']);

            ApplicationTraining::firstOrCreate(
                ['job_application_id' => $application->id],
                [
                    'start_date' => now(),
                    'end_date'   => now()->addDays(30),
                    'result'     => 'in_progress'
                ]
            );
        } else {
            $application->update(['status' => 'rejected']);
        }

        $this->notificationService->notifyStatusChanged($application->fresh());

        return ['status' => 200, 'data' => $interview->fresh()];
    }

    public function cancel(Interview $interview, $user, ?string $reason = null)
    {
        $application = JobApplication::with('jobPost.company')->findOrFail(
            $interview->job_application_id
        );

        if (
            $user->role !== 'admin' &&
            (!$application->jobPost || $application->jobPost->company_id != $user->company?->id)
        ) {
            abort(403, 'Unauthorized');
        }

        $interview->update([
            'status'   => 'cancelled',
            'feedback' => $reason
        ]);

        // ✅ إيميل عند الإلغاء
        $this->notificationService->notifyInterviewCancelled(
            $interview->fresh()->load('jobApplication.jobPost')
        );

        return ['status' => 200, 'data' => $interview->fresh()];
    }
}
