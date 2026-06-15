<?php

namespace App\Services;

use App\Models\CommentReaction;

class CommentReactionService
{
    public function react($userId, $commentId, $type)
    {
        return CommentReaction::updateOrCreate(
            [
                'user_id' => $userId,
                'comment_id' => $commentId
            ],
            [
                'type' => $type
            ]
        );
    }

    public function remove($userId, $commentId)
    {
        return CommentReaction::where([
            'user_id' => $userId,
            'comment_id' => $commentId
        ])->delete();
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
