<?php

namespace App\Http\Controllers;

use App\Models\SkillSuggestion;
use Illuminate\Http\Request;

class SkillSuggestionController extends Controller
{

    public function index(Request $request)
    {
        return response()->json(
            $request->user()
                ->skillSuggestions()
                ->latest()
                ->get()
        );
    }


    public function accept(Request $request, SkillSuggestion $suggestion)
    {
        abort_if($suggestion->user_id !== $request->user()->id, 403);

        $suggestion->update([
            'status' => 'accepted'
        ]);

        $request->user()->skills()->create([
            'name' => $suggestion->name,
            'type' => $suggestion->category ?? 'technical',
            'level' => 'beginner',
            'years_experience' => null
        ]);

        return response()->json([
            'message' => 'Skill accepted successfully'
        ]);
    }


    public function reject(Request $request, SkillSuggestion $suggestion)
    {
        abort_if($suggestion->user_id !== $request->user()->id, 403);

        $suggestion->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'Skill rejected successfully'
        ]);
    }


    public function destroy(Request $request, SkillSuggestion $suggestion)
    {
        abort_if($suggestion->user_id !== $request->user()->id, 403);

        $suggestion->delete();

        return response()->json([
            'message' => 'Suggestion deleted'
        ]);
    }
}
