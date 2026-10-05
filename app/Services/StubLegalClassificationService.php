<?php

namespace App\Services;

use App\Contracts\LegalClassificationServiceInterface;
use App\Models\CaseRecord;

class StubLegalClassificationService implements LegalClassificationServiceInterface
{
    public function classify(CaseRecord $case, ?string $reviewContext = null): array
    {
        return [
            'classification' => 'unclassified_pending_legal_review',
            'severity_signal' => 0,
            'cited_pasal' => [],
            'explanation' => 'Stub output only. No legal classification is available until an active corpus source is reviewed by a legal reviewer.',
            'draft_report' => 'A draft report based on the submitted narrative and evidence is ready for legal review.',
            'review_context_acknowledged' => $reviewContext !== null,
        ];
    }
}
