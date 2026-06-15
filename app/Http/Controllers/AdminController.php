<?php

namespace App\Http\Controllers;

use App\Services\AdminCompanyApprovalService;
use App\Services\AdminContentService;
use App\Services\AdminStatsService;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private readonly AdminStatsService           $statsService,
        private readonly AdminUserService            $userService,
        private readonly AdminCompanyApprovalService $companyService,
        private readonly AdminContentService         $contentService,
    ) {}

    // ─── Dashboard ─────────────────────────────────────────

    public function dashboard(): JsonResponse
    {
        return response()->json($this->statsService->dashboard());
    }

    // ─── Users ─────────────────────────────────────────────

    public function users(Request $request): JsonResponse
    {
        $request->validate([
            'role'     => 'nullable|in:user,company,admin',
            'status'   => 'nullable|in:active,banned',
            'search'   => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        return response()->json($this->userService->list($request->all()));
    }

    public function showUser(int $id): JsonResponse
    {
        return response()->json($this->userService->show($id));
    }

    public function banUser(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        return response()->json($this->userService->ban($id, $request->reason));
    }

    public function unbanUser(int $id): JsonResponse
    {
        return response()->json($this->userService->unban($id));
    }

    public function deleteUser(int $id): JsonResponse
    {
        return response()->json($this->userService->delete($id));
    }

    // ─── Companies ─────────────────────────────────────────

    public function companies(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => 'nullable|in:pending,approved,rejected',
            'search'   => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        return response()->json($this->companyService->list($request->all()));
    }

    public function showCompany(int $id): JsonResponse
    {
        return response()->json($this->companyService->show($id));
    }

    public function approveCompany(int $id): JsonResponse
    {
        return response()->json([
            'message' => 'Company approved',
            'data'    => $this->companyService->approve($id)
        ]);
    }

    public function rejectCompany(Request $request, int $id): JsonResponse
    {
        $request->validate(['rejection_reason' => 'required|string']);

        return response()->json([
            'message' => 'Company rejected',
            'data'    => $this->companyService->reject($id, $request->rejection_reason)
        ]);
    }

    // ─── Jobs — الأدمن يشوف ويحذف بس ──────────────────────

    public function jobs(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => 'nullable|in:draft,published,closed',
            'search'   => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        return response()->json($this->contentService->listJobs($request->all()));
    }

    public function showJob(int $id): JsonResponse
    {
        return response()->json($this->contentService->showJob($id));
    }

    // ✅ حذف مع سبب + إيميل للشركة
    public function deleteJob(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        return response()->json(
            $this->contentService->deleteJob($id, $request->reason)
        );
    }

    // ─── Projects — الأدمن يشوف ويحذف بس ─────────────────
    // ✅ الموافقة/الرفض بين الشركة والمستخدم مش من الأدمن

    public function projects(Request $request): JsonResponse
    {
        $request->validate([
            'status'       => 'nullable|in:active,closed,pending',
            'search'       => 'nullable|string',
            'support_type' => 'nullable|in:funding,mentoring,partnership',
            'per_page'     => 'nullable|integer|min:1|max:50',
        ]);

        return response()->json($this->contentService->listProjects($request->all()));
    }

    public function showProject(int $id): JsonResponse
    {
        return response()->json($this->contentService->showProject($id));
    }

    // ✅ حذف مع سبب + إيميل لصاحب المشروع
    public function deleteProject(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        return response()->json(
            $this->contentService->deleteProject($id, $request->reason)
        );
    }
}
