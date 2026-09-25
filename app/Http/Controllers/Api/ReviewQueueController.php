<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseRecord;
use App\Services\ReviewTriageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewQueueController extends Controller
{
    public function index(Request $request, ReviewTriageService $triage): JsonResponse
    {
        $this->authorize('viewReviewQueue', CaseRecord::class);
        $cases = CaseRecord::pendingReview()->with(['severityScore', 'evidenceItems'])->get();
        if ($request->boolean('sync_flag')) {
            $cases = $cases->filter(fn (CaseRecord $case) => $case->severityScore?->sync_flag);
        }
        $rows = $cases->map(fn (CaseRecord $case) => ['case' => $case, 'triage' => $triage->assess($case)])->all();
        usort($rows, fn ($left, $right) => [!($right['case']->severityScore?->sync_flag), -($right['triage']['confidence'] ?? -1), $left['case']->created_at->timestamp] <=> [!($left['case']->severityScore?->sync_flag), -($left['triage']['confidence'] ?? -1), $right['case']->created_at->timestamp]);
        return response()->json(['data' => array_map(fn ($row) => [
            'id' => $row['case']->id, 'public_case_id' => $row['case']->public_case_id, 'created_at' => $row['case']->created_at,
            'severity' => $row['case']->severityScore?->computed_severity, 'sync_flag' => $row['case']->severityScore?->sync_flag,
            'triage' => $row['triage'],
        ], $rows)]);
    }
}
