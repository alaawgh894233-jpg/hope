<?php

namespace App\Services;

use App\Models\CommentReaction;

class CommentReactionService
{
    public function react($userId, $commentId, $type)
    {
        $reaction = CommentReaction::updateOrCreate(
            [
                'user_id' => $userId,
                'comment_id' => $commentId
            ],
            [
                'type' => $type
            ]
        );

        return [
            'reaction' => $reaction,
            'total_reactions' => CommentReaction::where('comment_id', $commentId)->count(),
            'reaction_icons' => CommentReaction::where('comment_id', $commentId)
                ->distinct()
                ->pluck('type')
                ->values(),
        ];
    }

    public function remove($userId, $commentId)
    {
        CommentReaction::where([
            'user_id' => $userId,
            'comment_id' => $commentId
        ])->delete();

        return [
            'total_reactions' => CommentReaction::where('comment_id', $commentId)->count(),
            'reaction_icons' => CommentReaction::where('comment_id', $commentId)
                ->distinct()
                ->pluck('type')
                ->values(),
        ];
    }

    public function count($commentId)
    {
        return CommentReaction::where('comment_id', $commentId)->count();
    }

    public function getByComment($commentId)
    {
        return CommentReaction::with('user')
            ->where('comment_id', $commentId)
            ->get();
    }
}
