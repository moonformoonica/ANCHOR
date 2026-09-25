<?php

namespace App\Services;

use App\Models\CaseRecord;

class CaseOutputService
{
    /** @return array<string, mixed>|null */
    public function victimFinalOutput(CaseRecord $case): ?array
    {
        if (!in_array($case->status, ['approved', 'edited'], true)) {
            return null;
        }

        $review = $case->reviews()->latest('id')->first();
        if ($case->status === 'edited' && $review?->edited_output !== null) {
            return $review->edited_output;
        }

        $legal = $case->aiPassResults()->where('pass_type', 'legal_classification')->latest('id')->first();
        return [
            'classification' => data_get($legal?->raw_output, 'classification'),
            'citations' => $case->citations()->get(['law_name', 'pasal_reference'])->map(fn ($citation) => [
                'law_name' => $citation->law_name,
                'pasal_reference' => $citation->pasal_reference,
            ])->all(),
            'final_report' => data_get($legal?->raw_output, 'draft_report'),
        ];
    }

    /** @return array<string, mixed>|null */
    public function distressResponse(CaseRecord $case): ?array
    {
        $result = $case->aiPassResults()->where('pass_type', 'distress_check')->latest('id')->first();
        if (!data_get($result?->raw_output, 'distress_flag', false)) {
            return null;
        }

        return ['support_resources' => data_get($result->raw_output, 'support_resources', [])];
    }
}
