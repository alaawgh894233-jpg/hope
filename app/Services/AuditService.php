<?php

namespace App\Services;

use App\Jobs\AuditLogJob;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log(
        $user,
        string $action,
        ?string $model = null,
        $modelId = null,
        array $meta = []
    ): void {

        AuditLogJob::dispatch(
            userId: $user?->id,
            action: $action,
            model: $model,
            modelId: $modelId,
            ip: Request::ip(),
            userAgent: Request::header('User-Agent'),
            meta: $meta
        )->onQueue('audit');
    }
}
