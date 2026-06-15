<?php

namespace App\Services;

use App\Models\Reaction;

class ReactionService
{
    public function react($userId, $postId, $type)
    {
        return Reaction::updateOrCreate(
            [
                'user_id' => $userId,
                'job_post_id' => $postId
            ],
            [
                'type' => $type
            ]
        );
    }

    public function remove($userId, $postId)
    {
        return Reaction::where([
            'user_id' => $userId,
            'job_post_id' => $postId
        ])->delete();
    }

    public function getPostReactions($postId)
    {
        return Reaction::where('job_post_id', $postId)->get();
    }

    public function countByType($postId)
    {
        return Reaction::selectRaw('type, count(*) as total')
            ->where('job_post_id', $postId)
            ->groupBy('type')
            ->get();
    }
    public function totalReactions($postId)
    {
        return Reaction::where('job_post_id', $postId)->count();
    }
}
