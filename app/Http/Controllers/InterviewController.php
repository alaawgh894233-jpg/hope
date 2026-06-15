<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\InterviewService;
use App\Http\Requests\ScheduleInterviewRequest;

class InterviewController extends Controller
{
    public function __construct(
        private readonly InterviewService $service
    ) {}

    public function store(ScheduleInterviewRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // ✅ بنمرر $user للـ service بعد التعديل
        return response()->json(
            $this->service->schedule($request->validated(), $user)
        );
    }

    public function complete(Request $request, Interview $interview): JsonResponse
    {
        $request->validate([
            'passed'   => ['required', 'boolean'],
            'feedback' => ['nullable', 'string']
        ]);

        /** @var User $user */
        $user = auth()->user();

        // ✅ بنمرر $user بدل companyId
        return response()->json(
            $this->service->complete(
                $interview,
                $request->boolean('passed'),  // ✅ boolean() أأمن من ->passed
                $user,
                $request->feedback
            )
        );
    }

    public function cancel(Request $request, Interview $interview): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string']
        ]);

        /** @var User $user */
        $user = auth()->user();

        // ✅ بنمرر $user بدل companyId
        return response()->json(
            $this->service->cancel(
                $interview,
                $user,
                $request->reason
            )
        );
    }

    public function show(Interview $interview): JsonResponse
    {
        return response()->json($interview);
    }
}
