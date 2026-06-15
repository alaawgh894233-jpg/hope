<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\ReactCommentRequest;
use App\Services\CommentReactionService;

class CommentReactionController extends Controller
{
    public function __construct(
        private CommentReactionService $service
    ) {}

    public function react(ReactCommentRequest $request, $commentId)
    {
        $reaction = $this->service->react(
            auth()->id(),
            $commentId,
            $request->type
        );

        return response()->json([
            'message' => 'reaction added',
            'data' => $reaction
        ]);
    }

    public function remove($commentId)
    {
        $this->service->remove(auth()->id(), $commentId);

        return response()->json([
            'message' => 'reaction removed'
        ]);
    }

    public function count($commentId)
    {
        return response()->json([
            'count' => $this->service->count($commentId)
        ]);
    }

    public function list($commentId)
    {
        return response()->json(
            $this->service->getByComment($commentId)
        );
    }
}
