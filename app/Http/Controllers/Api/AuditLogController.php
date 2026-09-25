<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);
        $query = AuditLog::query()->orderByDesc('created_at');
        if ($request->filled('case_id')) { $query->where('case_id', $request->integer('case_id')); }
        if ($request->filled('actor_type')) { $query->where('actor_type', $request->string('actor_type')); }
        if ($request->filled('from')) { $query->where('created_at', '>=', $request->date('from')); }
        if ($request->filled('to')) { $query->where('created_at', '<=', $request->date('to')); }
        return response()->json(['data' => $query->paginate(min(100, max(1, $request->integer('per_page', 25))))]);
    }
}
