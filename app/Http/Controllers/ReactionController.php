<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reactions\ReactPostRequest;
use App\Services\ReactionService;

class ReactionController extends Controller
{
    public function __construct(
        private ReactionService $service
    ) {}

    public function react(ReactPostRequest $request, $postId)
    {
        $result = $this->service->react(
            auth()->id(),
            $postId,
            $request->type
        );

        return response()->json([
            'message' => 'Reaction Added',
            'data' => $result['reaction'],
            'total_reactions' => $result['total_reactions'],
            'reaction_icons' => $result['reaction_icons'],
        ]);
    }

    public function remove($postId)
    {
        $result = $this->service->remove(
            auth()->id(),
            $postId
        );

        return response()->json([
            'message' => 'Reaction Removed',
            'total_reactions' => $result['total_reactions'],
            'reaction_icons' => $result['reaction_icons'],
        ]);
    }

    public function list($postId)
    {
        $reactions = $this->service->getPostReactions($postId);

        $counts = [
            'like'       => $reactions->where('type', 'like')->count(),
            'love'       => $reactions->where('type', 'love')->count(),
            'support'    => $reactions->where('type', 'support')->count(),
            'insightful' => $reactions->where('type', 'insightful')->count(),
        ];

        // حذف الأنواع التي عددها 0
        $counts = array_filter($counts, fn ($count) => $count > 0);

        return response()->json([
            'total' => $reactions->count(),

            'counts' => $counts,

            'users' => $reactions->map(function ($reaction) {
                return [
                    'id'   => $reaction->user->id,
                    'name' => $reaction->user->name,
                    'type' => $reaction->type,
                ];
            })->values()
        ]);
    }


    public function stats($postId)
    {
        $data = $this->service->countByType($postId);

        return response()->json([
            'total' => $this->service->totalReactions($postId),
            'reactions' => $data
        ]);
    }
    public function total($postId)
    {
        return response()->json([
            'total' => $this->service->totalReactions($postId)
        ]);
    }
}
