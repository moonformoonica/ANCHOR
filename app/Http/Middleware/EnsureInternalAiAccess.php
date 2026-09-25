<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalAiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null || !$request->user()->tokenCan('internal:ai')) {
            abort(403, 'This endpoint requires an internal AI service token.');
        }
        return $next($request);
    }
}
