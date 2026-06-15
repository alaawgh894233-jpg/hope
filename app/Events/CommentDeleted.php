<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentDeleted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $commentId,
        public int $postId
    ) {}

    public function broadcastOn()
    {
        return new Channel('post.' . $this->postId);
    }

    public function broadcastAs()
    {
        return 'comment.deleted';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->commentId
        ];
    }
}
