<?php

namespace App\Http\Controllers;

use App\Services\SkillSuggestionService;
use Illuminate\Http\Request;

class SkillSuggestionAIController extends Controller
{
    public function __construct(
        protected SkillSuggestionService $service
    ) {}

    public function suggest(Request $request)
    {
        return response()->json(
            $this->service->suggest(
                $request->user(),
                $request->job_title,
                $request->job_description
            )
        );
    }
}
