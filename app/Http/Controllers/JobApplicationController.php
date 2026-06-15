<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\JobApplication\ApplyRequest;
use App\Http\Requests\JobApplication\UpdateStatusRequest;
use App\Services\JobApplicationService;
use Illuminate\Http\JsonResponse;

class JobApplicationController extends Controller
{
    public function __construct(
        private readonly JobApplicationService $service
    ) {}

    // 👤 Apply on job
    public function apply(ApplyRequest $request, $jobId): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // ✅ بنمرر cv_file منفصل للـ service
        return response()->json(
            $this->service->apply(
                $user,
                $jobId,
                $request->validated(),
                $request->file('cv_file')  // ✅ كان ناقص
            )
        );
    }

    // 🏢 Company/Admin: list applicants for a job
    public function list($jobId): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->listForCompany($user, $jobId)
        );
    }

    // 🏢 Company/Admin: update application status
    public function updateStatus(UpdateStatusRequest $request, $id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->updateStatus($user, $id, $request->status)
        );
    }
}
