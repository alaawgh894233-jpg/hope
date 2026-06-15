<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Comment $comment)
    {
        $this->comment->load('user');
    }

    public function broadcastOn()
    {
        return new Channel('post.' . $this->comment->job_post_id);
    }

    public function broadcastAs()
    {
        return 'comment.created';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->comment->id,
            'content' => $this->comment->content,
            'parent_id' => $this->comment->parent_id,
            'user' => $this->comment->user,
            'created_at' => $this->comment->created_at,
        ];
    }
}
