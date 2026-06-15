<?php

namespace App\Services;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPost;

class CompanyDashboardService
{
    public function getDashboard($user): array
    {
        $company = $user->company;

        if (!$company) {
            return ['status' => 403, 'message' => 'No company found'];
        }

        $jobIds = JobPost::where('company_id', $company->id)->pluck('id');

        return [
            'status' => 200,
            'data'   => [
                // 📊 إحصائيات عامة
                'stats' => [
                    'total_jobs'         => $jobIds->count(),
                    'active_jobs'        => JobPost::where('company_id', $company->id)
                        ->where('status', 'published')->count(),
                    'total_applications' => JobApplication::whereIn('job_post_id', $jobIds)->count(),
                    'pending_applications' => JobApplication::whereIn('job_post_id', $jobIds)
                        ->where('status', 'pending')->count(),
                    'interviews_scheduled' => JobApplication::whereIn('job_post_id', $jobIds)
                        ->where('status', 'interview')->count(),
                    'accepted'           => JobApplication::whereIn('job_post_id', $jobIds)
                        ->where('status', 'accepted')->count(),
                ],

                // 📋 آخر فرص العمل
                'recent_jobs' => JobPost::where('company_id', $company->id)
                    ->latest()
                    ->take(5)
                    ->get(['id', 'title', 'status', 'applications_count', 'created_at']),

                // 👥 آخر الطلبات
                'recent_applications' => JobApplication::whereIn('job_post_id', $jobIds)
                    ->with(['user:id,name,email', 'jobPost:id,title'])
                    ->latest()
                    ->take(10)
                    ->get(),
            ]
        ];
    }

    public function getJobs($user, array $filters): array
    {
        $company = $user->company;

        if (!$company) {
            return ['status' => 403, 'message' => 'No company found'];
        }

        $query = JobPost::where('company_id', $company->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min((int)($filters['per_page'] ?? 10), 50);

        return [
            'status' => 200,
            'data'   => $query->latest()->paginate($perPage)
        ];
    }

    public function getApplications($user, array $filters): array
    {
        $company = $user->company;

        if (!$company) {
            return ['status' => 403, 'message' => 'No company found'];
        }

        $jobIds = JobPost::where('company_id', $company->id)->pluck('id');

        $query = JobApplication::whereIn('job_post_id', $jobIds)
            ->with(['user:id,name,email', 'jobPost:id,title']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['job_id'])) {
            $query->where('job_post_id', $filters['job_id']);
        }

        $perPage = min((int)($filters['per_page'] ?? 10), 50);

        return [
            'status' => 200,
            'data'   => $query->latest()->paginate($perPage)
        ];
    }
}
