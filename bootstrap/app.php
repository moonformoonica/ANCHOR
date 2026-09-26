<?php

use App\Http\Middleware\AuditCaseAccess;
use App\Http\Middleware\EnsureInternalAiAccess;
use App\Http\Middleware\EnsureVictimCaseToken;
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
            'victim.case' => EnsureVictimCaseToken::class,
            'internal.ai' => EnsureInternalAiAccess::class,
        ]);
        $middleware->appendToGroup('api', AuditCaseAccess::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
