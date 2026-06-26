<?php

namespace App\Http\Controllers;

use App\Models\SkillSuggestion;
use Illuminate\Http\Request;

class SkillSuggestionController extends Controller
{
    // ══════════════════════════════════════════════════
    //  GET /api/skills/suggestions
    // ══════════════════════════════════════════════════
    public function index(Request $request)
    {
        return response()->json(
            $request->user()
                ->skillSuggestions()
                ->latest()
                ->get()
        );
    }

    // ══════════════════════════════════════════════════
    //  POST /api/skills/suggestions/{id}/accept
    // ══════════════════════════════════════════════════
    public function accept(Request $request, SkillSuggestion $suggestion)
    {
        abort_if($suggestion->user_id !== $request->user()->id, 403);

        // ✅ تحقق ما اتقبلت قبل
        if ($suggestion->status === 'accepted') {
            return response()->json([
                'message' => 'Skill already accepted',
            ], 409);
        }

        // ✅ حدّث الاقتراح + accepted_at
        $suggestion->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        // ✅ ضيف للمهارات — الـ type الصح من الاقتراح
        $request->user()->skills()->create([
            'name'             => $suggestion->name,
            'type'             => $suggestion->type ?? 'technical',
            'level'            => 'beginner',
            'years_experience' => null,
        ]);

        return response()->json([
            'message'    => 'Skill accepted successfully',
            'suggestion' => $suggestion->fresh(),
        ]);
    }

    // ══════════════════════════════════════════════════
    //  POST /api/skills/suggestions/{id}/reject
    // ══════════════════════════════════════════════════
    public function reject(Request $request, SkillSuggestion $suggestion)
    {
        abort_if($suggestion->user_id !== $request->user()->id, 403);

        $suggestion->update(['status' => 'rejected']);

        return response()->json([
            'message'    => 'Skill rejected successfully',
            'suggestion' => $suggestion->fresh(),
        ]);
    }

    // ══════════════════════════════════════════════════
    //  DELETE /api/skills/suggestions/{id}
    // ══════════════════════════════════════════════════
    public function destroy(Request $request, SkillSuggestion $suggestion)
    {
        abort_if($suggestion->user_id !== $request->user()->id, 403);

        $suggestion->delete();

        return response()->json([
            'message' => 'Suggestion deleted',
        ]);
    }
}
