<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $application,
        public $fromStageId,
        public $toStageId
    ) {}

    public function broadcastOn()
    {
        return new Channel('company.' . $this->application->jobPost->company_id);
    }

    public function broadcastAs()
    {
        return 'application.stage.changed';
    }
}
