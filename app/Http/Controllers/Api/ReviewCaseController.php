<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitReviewRequest;
use App\Models\CaseRecord;
use App\Services\ReviewStateMachine;
use App\Services\ReviewTriageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewCaseController extends Controller
{
    public function show(CaseRecord $case, ReviewTriageService $triage): JsonResponse
    {
        $this->authorize('viewForReview', $case);
        $case->load(['narrative', 'evidenceItems', 'aiPassResults', 'citations', 'severityScore', 'reviews.reviewer']);
        return response()->json(['case' => $case, 'triage' => $triage->assess($case)]);
    }

    public function review(SubmitReviewRequest $request, CaseRecord $case, ReviewStateMachine $stateMachine): JsonResponse
    {
        $this->authorize('review', $case);
        $review = $stateMachine->apply($case, $request->user(), $request->validated());
        return response()->json(['review' => $review, 'case_status' => $case->fresh()->status]);
    }
}
