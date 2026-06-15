<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuditService;

class AuditMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->method() === 'GET') {
            return $response;
        }

        $user = $request->user();

        AuditService::log(
            $user,
            $request->method() . ' ' . $request->path(),
            null,
            null,
            [
                'status' => $response->getStatusCode(),
                'input' => $request->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                    'new_password',
                    'otp',
                    'profile_image',
                    'cv_file',
                ])
            ]
        );

        return $response;
    }
}
