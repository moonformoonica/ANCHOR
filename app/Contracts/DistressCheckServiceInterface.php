<?php

namespace App\Contracts;

use App\Models\CaseRecord;

interface DistressCheckServiceInterface
{
    /** @return array{distress_flag: bool, support_resources: array<int, array<string, string>>} */
    public function check(CaseRecord $case, ?string $reviewContext = null): array;
}
