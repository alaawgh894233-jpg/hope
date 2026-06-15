<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Services\RuleEngineService;
use Illuminate\Http\Request;

class RuleEngineController extends Controller
{
    public function __construct(
        private RuleEngineService $ruleEngine
    ) {}

    public function evaluate($id)
    {
        $application = JobApplication::with('user')->findOrFail($id);

        $result = $this->ruleEngine->evaluate($application);

        return response()->json([
            'application_id' => $application->id,
            'decision' => $result ?? 'no_rule_matched'
        ]);
    }
}
