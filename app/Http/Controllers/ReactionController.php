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
        $this->service->react(
            auth()->id(),
            $postId,
            $request->type
        );

        return response()->json([
            'message' => 'Reaction Added'
        ]);
    }

    public function remove($postId)
    {
        $this->service->remove(
            auth()->id(),
            $postId
        );

        return response()->json([
            'message' => 'Reaction Removed'
        ]);
    }

    public function list($postId)
    {
        return response()->json(
            $this->service->getPostReactions($postId)
        );
    }

    public function stats($postId)
    {
        return response()->json(
            $this->service->countByType($postId)
        );
    }
    public function total($postId)
    {
        return response()->json([
            'total' => $this->service->totalReactions($postId)
        ]);
    }
}
