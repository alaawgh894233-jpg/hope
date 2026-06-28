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
        $result = $this->service->react(
            auth()->id(),
            $commentId,
            $request->type
        );

        return response()->json([
            'message' => 'Reaction Added',
            'data' => $result['reaction'],
            'total_reactions' => $result['total_reactions'],
            'reaction_icons' => $result['reaction_icons'],
        ]);
    }

    public function remove($commentId)
    {
        $result = $this->service->remove(
            auth()->id(),
            $commentId
        );

        return response()->json([
            'message' => 'Reaction Removed',
            'total_reactions' => $result['total_reactions'],
            'reaction_icons' => $result['reaction_icons'],
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
