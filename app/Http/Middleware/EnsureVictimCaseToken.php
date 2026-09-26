<?php

namespace App\Http\Middleware;

use App\Models\CaseRecord;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureVictimCaseToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $case = CaseRecord::query()->where('public_case_id', $request->route('public_case_id'))->first();
        if ($token === null || $case === null || ! Hash::check($token, $case->token_hash)) {
            abort(401, 'A valid bearer token is required for this case.');
        }
        $request->attributes->set('anchor.case', $case);

        return $next($request);
    }
}
