<?php

namespace App\Services;

use App\Events\CommentCreated;
use App\Events\CommentUpdated;
use App\Events\CommentDeleted;
use App\Models\Comment;

class CommentService
{
    public function create($userId, $postId, array $data)
    {
        $comment = Comment::create([
            'user_id' => $userId,
            'job_post_id' => $postId,
            'content' => $data['content'],
            'parent_id' => $data['parent_id'] ?? null
        ]);

        $comment->load('user');

        broadcast(new CommentCreated($comment))->toOthers();

        return [
            'comment' => $comment,
            'total_comments' => Comment::where('job_post_id', $postId)->count(),
        ];
    }

    public function update($commentId, $userId, $content)
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->user_id !== $userId) {
            abort(403,   'Unauthorized');
        }

        $comment->update([
            'content' => $content
        ]);

        $comment->load(['user']);

        // 🔥 مهم: broadcast update event
        broadcast(new CommentUpdated($comment))->toOthers();

        return $comment;
    }

    public function delete($commentId, $userId)
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->user_id !== $userId) {
            abort(403, 'Unauthorized');
        }

        $postId = $comment->job_post_id;
        $id = $comment->id;

        $comment->delete();

        broadcast(new CommentDeleted($id, $postId))->toOthers();

        return [
            'total_comments' => Comment::where('job_post_id', $postId)->count(),
        ];
    }
    public function getCommentsCount($postId)
    {
        return Comment::where('job_post_id', $postId)->count();
    }


    public function getByPost($postId)
    {
        return Comment::with([
            'user',
            'replies.user',
            'reactions'
        ])
            ->where('job_post_id', $postId)
            ->whereNull('parent_id')
            ->latest()
            ->get();
    }
}
