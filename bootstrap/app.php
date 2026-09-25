<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'victim.case' => \App\Http\Middleware\EnsureVictimCaseToken::class,
            'internal.ai' => \App\Http\Middleware\EnsureInternalAiAccess::class,
        ]);
        $middleware->appendToGroup('api', \App\Http\Middleware\AuditCaseAccess::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
