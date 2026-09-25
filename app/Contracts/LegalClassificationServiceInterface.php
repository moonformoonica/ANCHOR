<?php

namespace App\Contracts;

use App\Models\CaseRecord;

interface LegalClassificationServiceInterface
{
    /** @return array<string, mixed> */
    public function classify(CaseRecord $case, ?string $reviewContext = null): array;
}
