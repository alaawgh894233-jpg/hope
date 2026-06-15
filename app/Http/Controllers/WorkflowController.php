<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\User;
use App\Models\WorkflowRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\HiringWorkflowEngineService;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly HiringWorkflowEngineService $service
    ) {}

    public function move(Request $request, $id): JsonResponse
    {
        $request->validate([
            'to_stage_id' => 'required|integer|exists:workflow_stages,id',
            'note'        => 'nullable|string'
        ]);

        /** @var User $user */
        $user = auth()->user();

        // ✅ authorization — Admin أو صاحب الـ job بس
        $application = JobApplication::with('jobPost')->findOrFail($id);

        if (
            $user->role !== 'admin' &&
            $application->jobPost?->company_id !== $user->company?->id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(
            $this->service->moveToStage(
                $id,
                $request->to_stage_id,
                auth()->id(),
                $request->note
            )
        );
    }

    public function pipeline($id): JsonResponse
    {
        $application = JobApplication::with([
            'workflow.stages',
            'currentStage',
            'stageHistory.fromStage',
            'stageHistory.toStage',
            'stageHistory.user'
        ])->findOrFail($id);

        return response()->json([
            'application'   => $application,
            'workflow'      => $application->workflow,
            'stages'        => $application->workflow?->stages,
            'current_stage' => $application->currentStage,
            'history'       => $application->stageHistory
        ]);
    }

    public function index($workflowId): JsonResponse
    {
        return response()->json(
            WorkflowRule::where('workflow_id', $workflowId)
                ->orderBy('priority')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workflow_id' => 'required|integer|exists:workflows,id',
            'name'        => 'required|string|max:255',
            'priority'    => 'required|integer|min:0',
            'conditions'  => 'nullable|array',
            'actions'     => 'nullable|array',
        ]);

        return response()->json(WorkflowRule::create($validated), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'priority'   => 'sometimes|integer|min:0',
            'conditions' => 'nullable|array',
            'actions'    => 'nullable|array',
        ]);

        $rule = WorkflowRule::findOrFail($id);
        $rule->update($validated);

        return response()->json($rule);
    }

    public function destroy($id): JsonResponse
    {
        WorkflowRule::findOrFail($id)->delete();

        return response()->json(['message' => 'deleted']);
    }
}
