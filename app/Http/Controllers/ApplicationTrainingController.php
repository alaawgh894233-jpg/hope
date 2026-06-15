<?php

namespace App\Http\Controllers;

use App\Models\ApplicationTraining;
use App\Models\User;
use App\Services\ApplicationTrainingService;
use App\Http\Requests\CreateApplicationTrainingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationTrainingController extends Controller
{
    public function __construct(
        private readonly ApplicationTrainingService $service  // ✅ readonly
    ) {}

    public function store(CreateApplicationTrainingRequest $request): JsonResponse  // ✅ return type
    {
        return response()->json(
            $this->service->create($request->validated()),
            201  // ✅ 201 للـ create
        );
    }

    public function evaluate(
        Request $request,
        ApplicationTraining $training
    ): JsonResponse  // ✅ return type
    {
        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string'
        ]);

        return response()->json(
            $this->service->evaluate(
                $training,
                auth()->user()->company->id,
                (int) $validated['score'],
                $validated['notes'] ?? null
            )

        );
    }
}
