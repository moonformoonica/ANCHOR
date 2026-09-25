<?php

namespace App\Services;

use App\Contracts\LegalClassificationServiceInterface;
use App\Models\CaseRecord;

class StubLegalClassificationService implements LegalClassificationServiceInterface
{
    public function classify(CaseRecord $case, ?string $reviewContext = null): array
    {
        return [
            'classification' => 'suspected_unlawful_electronic_defamation_or_threat',
            'severity_signal' => 65,
            'cited_pasal' => [['law_name' => 'UU ITE', 'pasal_reference' => 'Pasal 27 ayat (3)']],
            'explanation' => 'Stub classification only. A legal reviewer must verify this output before it is authoritative.',
            'draft_report' => 'A draft report based on the submitted narrative and evidence is ready for legal review.',
            'review_context_acknowledged' => $reviewContext !== null,
        ];
    }
}
