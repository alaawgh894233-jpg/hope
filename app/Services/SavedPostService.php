<?php

namespace App\Services;

use App\Models\SavedPost;

class SavedPostService
{
    public function toggle($userId, $postId)
    {
        $saved = SavedPost::where([
            'user_id' => $userId,
            'job_post_id' => $postId
        ])->first();

        if ($saved) {
            $saved->delete();

            return 'unsaved';
        }

        SavedPost::create([
            'user_id' => $userId,
            'job_post_id' => $postId
        ]);

        return 'saved';
    }

    public function getMySaved($userId)
    {
        return SavedPost::with('post')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function isSaved($userId, $postId)
    {
        return SavedPost::where([
            'user_id' => $userId,
            'job_post_id' => $postId
        ])->exists();
    }
}
