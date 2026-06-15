<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\JobPost;
use Illuminate\Http\UploadedFile;

class JobApplicationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    // 👤 Apply
    public function apply($user, $jobId, array $data, ?UploadedFile $cvFile = null)
    {
        $job = JobPost::findOrFail($jobId);

        if (in_array($user->role, ['company', 'admin'])) {
            return ['status' => 403, 'message' => 'Only users can apply for jobs'];
        }

        if ($job->status !== 'published') {
            return ['status' => 403, 'message' => 'Job not available'];
        }

        if ($job->expires_at && $job->expires_at->isPast()) {
            return ['status' => 403, 'message' => 'Job expired'];
        }

        if ($job->company_id === $user->company?->id) {
            return ['status' => 403, 'message' => 'Cannot apply to your own job'];
        }

        $exists = JobApplication::where([
            'user_id'     => $user->id,
            'job_post_id' => $jobId
        ])->exists();

        if ($exists) {
            return ['status' => 409, 'message' => 'Already applied'];
        }

        $cvSnapshot = [
            'skills'      => $user->skills,
            'experiences' => $user->experiences,
            'educations'  => $user->educations,
            'projects'    => $user->projects,
        ];

        $cvFilePath = null;
        if ($cvFile) {
            $cvFilePath = $cvFile->store('applications/cv', 'public');
        }

        $application = JobApplication::create([
            'user_id'      => $user->id,
            'job_post_id'  => $jobId,
            'cover_letter' => $data['cover_letter'] ?? null,
            'cv_snapshot'  => $cvSnapshot,
            'cv_file'      => $cvFilePath,
            'status'       => 'pending'
        ]);

        $job->increment('applications_count');

        return [
            'status'  => 200,
            'message' => 'Applied successfully',
            'data'    => $application
        ];
    }

    // 🏢 Company: list applications
    public function listForCompany($user, $jobId)
    {
        $job = JobPost::findOrFail($jobId);

        if ($user->role !== 'admin' && $job->company_id !== $user->company?->id) {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        $applications = JobApplication::where('job_post_id', $jobId)
            ->with('user')
            ->latest()
            ->get();

        return ['status' => 200, 'data' => $applications];
    }

    // 🏢 Company: update status
    public function updateStatus($user, $id, $status)
    {
        $application = JobApplication::with('user', 'jobPost')->findOrFail($id);
        $job         = $application->jobPost;

        if ($user->role !== 'admin' && $job->company_id !== $user->company?->id) {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        $application->update(['status' => $status]);

        // ✅ إيميل مباشر للـ user
        $this->notificationService->notifyStatusChanged($application->fresh());

        return [
            'status'  => 200,
            'message' => 'Status updated',
            'data'    => $application
        ];
    }
}
