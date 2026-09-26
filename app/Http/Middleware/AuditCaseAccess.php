<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\CaseRecord;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditCaseAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $case = $request->attributes->get('anchor.case');
        if (! $case instanceof CaseRecord && $request->route('case') instanceof CaseRecord) {
            $case = $request->route('case');
        }
        $user = $request->user();
        $actorType = $case instanceof CaseRecord && $user === null ? 'victim' : 'system';
        if ($user !== null) {
            $actorType = $user->hasRole('admin') ? 'admin' : 'reviewer';
        }
        AuditLog::create([
            'actor_type' => $actorType,
            'actor_id' => $user?->id,
            'action' => sprintf('%s %s', $request->method(), $request->route()?->getName() ?? $request->path()),
            'case_id' => $case?->id,
            'details' => ['route' => $request->route()?->getName(), 'method' => $request->method(), 'response_status' => $response->getStatusCode()],
            'created_at' => now(),
        ]);

        return $response;
    }
}
