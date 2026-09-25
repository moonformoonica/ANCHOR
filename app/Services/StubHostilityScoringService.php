<?php

namespace App\Services;

use App\Contracts\HostilityScoringServiceInterface;
use App\Models\CaseRecord;

class StubHostilityScoringService implements HostilityScoringServiceInterface
{
    public function score(CaseRecord $case, ?string $reviewContext = null): array
    {
        return ['score' => 68, 'rationale' => 'Stub score for development only; it requires human review.'];
    }
}
