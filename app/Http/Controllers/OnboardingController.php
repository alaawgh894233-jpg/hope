<?php

namespace App\Http\Controllers;

use App\Models\UserOnboarding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $onboarding = UserOnboarding::firstOrCreate(
            ['user_id' => $user->id],
            [
                'user_type'       => $user->role,
                'current_step'    => 1,
                'total_steps'     => 6,
                'completed_steps' => [],
            ]
        );

        $steps = UserOnboarding::applicantSteps();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'onboarding'          => $onboarding,
                'steps'               => $steps,
                'progress_percentage' => $onboarding->getProgressPercentage(),
                'is_completed'        => $onboarding->is_completed,
                'is_skipped'          => $onboarding->is_skipped,
                'current_step'        => $onboarding->current_step,
                'next_step'           => $steps[$onboarding->current_step] ?? null,
            ],
        ]);
    }

    public function completeStep(Request $request, int $step): JsonResponse
    {
        $onboarding = UserOnboarding::where('user_id', $request->user()->id)->firstOrFail();

        if ($step < 1 || $step > $onboarding->total_steps) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid step number',
            ], 422);
        }

        $onboarding->completeStep($step);

        return response()->json([
            'status'              => 'success',
            'message'             => 'Step completed',
            'progress_percentage' => $onboarding->getProgressPercentage(),
            'is_completed'        => $onboarding->is_completed,
            'next_step'           => $onboarding->current_step,
        ]);
    }

    public function skip(Request $request): JsonResponse
    {
        $onboarding = UserOnboarding::where('user_id', $request->user()->id)->firstOrFail();

        $onboarding->update([
            'is_skipped' => true,
            'skipped_at' => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Onboarding skipped',
        ]);
    }

    public function restart(Request $request): JsonResponse
    {
        UserOnboarding::where('user_id', $request->user()->id)->update([
            'current_step'    => 1,
            'completed_steps' => [],
            'is_completed'    => false,
            'completed_at'    => null,
            'is_skipped'      => false,
            'skipped_at'      => null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Onboarding restarted',
        ]);
    }
}
