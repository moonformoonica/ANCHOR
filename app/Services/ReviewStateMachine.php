<?php

namespace App\Services;

use App\Models\CaseRecord;
use App\Models\Review;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReviewStateMachine
{
    public function __construct(private CaseProcessingService $processing) {}

    /** @param array<string, mixed> $input */
    public function apply(CaseRecord $case, User $reviewer, array $input): Review
    {
        if ($case->status !== 'pending_review') {
            throw ValidationException::withMessages(['case' => 'Only cases pending review can receive a review action.']);
        }

        $action = $input['action'];
        $review = $case->reviews()->create([
            'reviewer_id' => $reviewer->id,
            'action' => $action,
            'rejection_route' => $input['rejection_route'] ?? null,
            'edited_output' => $input['edited_output'] ?? null,
            'notes' => $input['notes'] ?? null,
        ]);

        if ($action === 'approve') {
            $case->update(['status' => 'approved']);
        } elseif ($action === 'edit') {
            $case->update(['status' => 'edited']);
        } elseif ($input['rejection_route'] === 'manual_handling') {
            $case->update(['status' => 'manual_handling']);
        } else {
            $case->update(['status' => 'reclassify_pending']);
            $this->processing->process($case->fresh(), $input['notes'] ?? null);
        }

        return $review;
    }
}
