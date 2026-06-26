<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\User;
use App\Models\WorkflowRule;
use App\Models\WorkflowStage;
use App\Services\HiringWorkflowEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly HiringWorkflowEngineService $service
    ) {}

    // ══════════════════════════════════════════════════
    //  POST /api/applications/{id}/move-stage
    // ══════════════════════════════════════════════════
    public function move(Request $request, $id): JsonResponse
    {
        $request->validate([
            'to_stage_id' => 'required|integer|exists:workflow_stages,id',
            'note'        => 'nullable|string|max:1000',
        ]);


        $user        = auth()->user();
        $application = JobApplication::with('jobPost')->findOrFail($id);

        // ✅ authorization
        if (
            $user->role !== 'admin' &&
            $application->jobPost?->company_id !== $user->company?->id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $result = $this->service->moveToStage(
            $id,
            $request->to_stage_id,
            auth()->id(),
            $request->note
        );

        return response()->json($result, $result['status'] ?? 200);
    }

    // ══════════════════════════════════════════════════
    //  GET /api/applications/{id}/pipeline
    // ══════════════════════════════════════════════════
    public function pipeline($id): JsonResponse
    {
        $application = JobApplication::with([
            'workflow.stages' => fn($q) => $q->orderBy('order_index'),
            'currentStage',
            'stageHistory.fromStage',
            'stageHistory.toStage',
            'stageHistory.changer',
        ])->findOrFail($id);

        return response()->json([
            'application'     => $application,
            'workflow'        => $application->workflow,
            'stages'          => $application->workflow?->stages,
            'current_stage'   => $application->currentStage,
            'history'         => $application->stageHistory,
            'available_stages'=> $this->service->getAvailableStages($id),
        ]);
    }

    // ══════════════════════════════════════════════════
    //  POST /api/applications/{id}/evaluate-rules
    // ══════════════════════════════════════════════════
    public function evaluate(Request $request, $id): JsonResponse
    {
        $application = JobApplication::with([
            'workflow.rules' => fn($q) => $q->orderBy('priority'),
            'currentStage',
        ])->findOrFail($id);

        $results     = [];
        $autoActions = [];

        foreach ($application->workflow?->rules ?? [] as $rule) {
            // ✅ الـ DB فيه field/operator/value مش conditions array
            $matched = $this->evaluateRule($rule, $application);

            $results[] = [
                'rule_id'         => $rule->id,
                'rule'            => $rule->name,
                'priority'        => $rule->priority,
                'condition'       => "{$rule->field} {$rule->operator} {$rule->value}",
                'matched'         => $matched,
                'action'          => $matched ? $rule->action : null,
                'target_stage_id' => $matched ? $rule->target_stage_id : null,
            ];

            if ($matched) {
                $autoActions[] = [
                    'action'          => $rule->action,
                    'target_stage_id' => $rule->target_stage_id,
                ];
            }
        }

        return response()->json([
            'application_id'  => $id,
            'current_stage'   => $application->currentStage?->name,
            'rules_evaluated' => count($results),
            'matched_count'   => count($autoActions),
            'results'         => $results,
            'auto_actions'    => $autoActions,
        ]);
    }

    private function evaluateRule(WorkflowRule $rule, JobApplication $application): bool
    {
        $actual   = data_get($application, $rule->field);
        $value    = $rule->value;
        $operator = $rule->operator;

        return match ($operator) {
            '>='     => $actual >= $value,
            '<='     => $actual <= $value,
            '>'      => $actual >  $value,
            '<'      => $actual <  $value,
            '!='     => $actual != $value,
            'in'     => in_array($actual, explode(',', $value)),
            'not_in' => !in_array($actual, explode(',', $value)),
            default  => $actual == $value,
        };
    }
    // ══════════════════════════════════════════════════
    //  GET /api/workflows/{id}/rules
    // ══════════════════════════════════════════════════
    public function index($workflowId): JsonResponse
    {
        return response()->json(
            WorkflowRule::where('workflow_id', $workflowId)
                ->orderBy('priority')
                ->get()
        );
    }

    // ── store ── متوافق مع columns الـ DB
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workflow_id'     => 'required|integer|exists:hiring_workflows,id',
            'name'            => 'required|string|max:255',
            'field'           => 'required|string|max:255',
            'operator'        => 'required|string|in:=,!=,>,<,>=,<=,in,not_in',
            'value'           => 'required|string',
            'action'          => 'required|string',
            'score_weight'    => 'nullable|integer|min:0',
            'priority'        => 'required|integer|min:0',
            'group_logic'     => 'nullable|in:AND,OR',
            'target_stage_id' => 'nullable|integer|exists:workflow_stages,id',
        ]);

        return response()->json(WorkflowRule::create($validated), 201);
    }

// ── update ──
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'name'            => 'sometimes|string|max:255',
            'field'           => 'sometimes|string|max:255',
            'operator'        => 'sometimes|string|in:=,!=,>,<,>=,<=,in,not_in',
            'value'           => 'sometimes|string',
            'action'          => 'sometimes|string',
            'score_weight'    => 'nullable|integer|min:0',
            'priority'        => 'sometimes|integer|min:0',
            'group_logic'     => 'nullable|in:AND,OR',
            'target_stage_id' => 'nullable|integer|exists:workflow_stages,id',
        ]);

        $rule = WorkflowRule::findOrFail($id);
        $rule->update($validated);

        return response()->json($rule);
    }
    // ══════════════════════════════════════════════════
    //  DELETE /api/rules/{id}
    // ══════════════════════════════════════════════════
    public function destroy($id): JsonResponse
    {
        WorkflowRule::findOrFail($id)->delete();

        return response()->json(['message' => 'Rule deleted successfully']);
    }

    // ══════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════

    private function evaluateConditions(array $conditions, JobApplication $application): bool
    {
        if (empty($conditions)) return false;

        foreach ($conditions as $field => $condition) {
            $operator = $condition['operator'] ?? '=';
            $value    = $condition['value']    ?? null;
            $actual   = data_get($application, $field);

            $match = match ($operator) {
                '>='    => $actual >= $value,
                '<='    => $actual <= $value,
                '>'     => $actual >  $value,
                '<'     => $actual <  $value,
                '!='    => $actual != $value,
                'in'    => in_array($actual, (array) $value),
                'not_in'=> !in_array($actual, (array) $value),
                default => $actual == $value,
            };

            if (!$match) return false;
        }

        return true;
    }
}
