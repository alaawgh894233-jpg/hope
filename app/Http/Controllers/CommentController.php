<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $service
    ) {}

    public function index($postId)
    {
        return $this->service->getByPost($postId);
    }

    public function store(StoreCommentRequest $request, $postId)
    {
        $result = $this->service->create(
            auth()->id(),
            $postId,
            $request->validated()
        );

        return response()->json([
            'message' => 'Comment Added',
            'data' => $result['comment'],
            'total_comments' => $result['total_comments'],
        ]);
    }

    public function update(UpdateCommentRequest $request, $id)
    {
        return $this->service->update(
            $id,
            auth()->id(),
            $request->validated()['content']
        );
    }

    public function destroy($id)
    {
        $result = $this->service->delete(
            $id,
            auth()->id()
        );

        return response()->json([
            'message' => 'Comment Deleted',
            'total_comments' => $result['total_comments'],
        ]);
    }

    public function commentsCount($postId)
    {
        $count = $this->service->getCommentsCount($postId);

        return response()->json([
            'total_comments' => $count
        ]);
    }
}
