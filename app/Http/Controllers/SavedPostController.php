<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleSavedPostRequest;
use App\Services\SavedPostService;

class SavedPostController extends Controller
{
    public function __construct(
        private SavedPostService $service
    ) {}

    public function toggle(ToggleSavedPostRequest $request)
    {
        $result = $this->service->toggle(
            auth()->id(),
            $request->job_post_id
        );

        return response()->json([
            'message' => $result
        ]);
    }

    public function mySaved()
    {
        return response()->json(
            $this->service->getMySaved(auth()->id())
        );
    }

    public function isSaved($postId)
    {
        return response()->json([
            'saved' => $this->service->isSaved(auth()->id(), $postId)
        ]);
    }
}
