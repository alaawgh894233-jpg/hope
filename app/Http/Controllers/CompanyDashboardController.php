<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CompanyDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyDashboardController extends Controller
{
    public function __construct(
        private readonly CompanyDashboardService $service
    ) {}

    // 📊 لوحة التحكم الرئيسية
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->getDashboard($user)
        );
    }

    // 📋 فرص العمل
    public function jobs(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => 'nullable|in:draft,published,closed',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->getJobs($user, $request->all())
        );
    }

    // 👥 طلبات التوظيف
    public function applications(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => 'nullable|in:pending,interview,training,accepted,rejected',
            'job_id'   => 'nullable|integer|exists:job_posts,id',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->getApplications($user, $request->all())
        );
    }
}
