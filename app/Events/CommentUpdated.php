<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentUpdated implements ShouldBroadcast
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
        return 'comment.updated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->comment->id,
            'content' => $this->comment->content,
            'updated_at' => $this->comment->updated_at,
            'user' => $this->comment->user,
        ];
    }
}
