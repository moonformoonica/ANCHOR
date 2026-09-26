<?php

namespace App\Policies;

use App\Models\CaseRecord;
use App\Models\User;

class CaseRecordPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewReviewQueue(User $user): bool
    {
        return $user->hasRole('legal_reviewer');
    }

    public function viewForReview(User $user, CaseRecord $case): bool
    {
        return $user->hasRole('legal_reviewer');
    }

    public function review(User $user, CaseRecord $case): bool
    {
        return $user->hasRole('legal_reviewer');
    }
}
