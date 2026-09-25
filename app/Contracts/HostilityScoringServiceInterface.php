<?php

namespace App\Contracts;

use App\Models\CaseRecord;

interface HostilityScoringServiceInterface
{
    /** @return array{score: int, rationale: string} */
    public function score(CaseRecord $case, ?string $reviewContext = null): array;
}
