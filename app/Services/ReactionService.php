<?php

namespace App\Services;

use App\Models\Reaction;

class ReactionService
{
    public function react($userId, $postId, $type)
    {
        $reaction = Reaction::updateOrCreate(
            [
                'user_id' => $userId,
                'job_post_id' => $postId
            ],
            [
                'type' => $type
            ]
        );

        return [
            'reaction' => $reaction,
            'total_reactions' => Reaction::where('job_post_id', $postId)->count(),
            'reaction_icons' => Reaction::where('job_post_id', $postId)
                ->distinct()
                ->pluck('type')
                ->values(),
        ];
    }

    public function remove($userId, $postId)
    {
        Reaction::where([
            'user_id' => $userId,
            'job_post_id' => $postId
        ])->delete();

        return [
            'total_reactions' => Reaction::where('job_post_id', $postId)->count(),
            'reaction_icons' => Reaction::where('job_post_id', $postId)
                ->distinct()
                ->pluck('type')
                ->values(),
        ];
    }
    public function getPostReactions($postId)
    {
        return Reaction::with('user:id,name')
            ->where('job_post_id', $postId)
            ->get();
    }


    public function countByType($postId)
    {
        return Reaction::with('user:id,name')
            ->where('job_post_id', $postId)
            ->get()
            ->groupBy('type')
            ->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'users' => $items->map(function ($reaction) {
                        return [
                            'id' => $reaction->user->id,
                            'name' => $reaction->user->name,
                        ];
                    })->values()
                ];
            });
    }

    public function totalReactions($postId)
    {
        return Reaction::where('job_post_id', $postId)->count();
    }
}
