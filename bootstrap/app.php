<?php

use App\Http\Middleware\AuditMiddleware;
use App\Models\AccountDeletionSchedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'audit' => AuditMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'company.approved' => \App\Http\Middleware\EnsureCompanyApproved::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {


    })

         ->withSchedule(function () {
        Schedule::call(function () {

            $items = AccountDeletionSchedule::where(
                'scheduled_for',
                '<=',
                now()
            )->get();

            foreach ($items as $item) {
                $item->user?->delete();
                $item->delete();
            }

        })->daily();
    })->create();
