<?php

namespace App\Http\Middleware;

use Closure;

class EnsureCompanyApproved
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->role !== 'company') {
            return response()->json(['message' => 'Only companies allowed'], 403);
        }

        if (!$user->company || $user->company->status !== 'approved') {
            return response()->json([
                'message' => 'Company not approved yet'
            ], 403);
        }

        return $next($request);
    }
}
