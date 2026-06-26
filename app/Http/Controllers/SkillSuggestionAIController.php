<?php

namespace App\Http\Controllers;

use App\Services\SkillSuggestionService;
use Illuminate\Http\Request;

class SkillSuggestionAIController extends Controller
{
    public function __construct(
        protected SkillSuggestionService $service
    ) {}

    // ══════════════════════════════════════════════════
    //  POST /api/skills/suggest
    // ══════════════════════════════════════════════════
    public function suggest(Request $request)
    {
        $validated = $request->validate([
            'job_title'       => 'nullable|string|max:255',
            'job_description' => 'nullable|string|max:5000',
        ]);

        try {
            $result = $this->service->suggest(
                $request->user(),
                $validated['job_title']       ?? null,
                $validated['job_description'] ?? null
            );

            return response()->json([
                'message' => 'Suggestions generated successfully',
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate suggestions',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
