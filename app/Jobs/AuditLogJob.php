<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AuditLogJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public ?int $userId,
        public string $action,
        public ?string $model = null,
        public $modelId = null,
        public ?string $ip = null,
        public ?string $userAgent = null,
        public array $meta = []
    ) {}

    public function handle(): void
    {
        AuditLog::create([
            'user_id'    => $this->userId,
            'action'     => $this->action,
            'model'      => $this->model,
            'model_id'   => $this->modelId,
            'ip'         => $this->ip,
            'user_agent' => $this->userAgent,
            'meta'       => $this->meta,
        ]);
    }
}
