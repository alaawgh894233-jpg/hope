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
        return $this->service->create(
            auth()->id(),
            $postId,
            $request->validated()
        );
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
        $this->service->delete($id, auth()->id());

        return response()->json(['message' => 'deleted']);
    }

    public function commentsCount($postId)
    {
        $count = $this->service->getCommentsCount($postId);

        return response()->json([
            'total_comments' => $count
        ]);
    }
}
