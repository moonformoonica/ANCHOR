<?php

namespace App\Services;

use App\Contracts\DistressCheckServiceInterface;
use App\Contracts\HostilityScoringServiceInterface;
use App\Contracts\LegalClassificationServiceInterface;
use App\Models\AiPassResult;
use App\Models\CaseRecord;
use App\Models\LegalCorpusEntry;

class CaseProcessingService
{
    public function __construct(
        private LegalClassificationServiceInterface $legal,
        private HostilityScoringServiceInterface $hostility,
        private DistressCheckServiceInterface $distress,
        private SeverityCombinationService $combination,
    ) {}

    public function process(CaseRecord $case, ?string $reviewContext = null): void
    {
        $case->update(['status' => 'processing']);
        $this->record($case, 'legal_classification', $this->legal->classify($case, $reviewContext));
        $this->record($case, 'hostility_score', $this->hostility->score($case, $reviewContext));
        $this->record($case, 'distress_check', $this->distress->check($case, $reviewContext));
        $this->syncCitations($case);
        $this->combination->recompute($case);
    }

    /** @param array<string, mixed> $output */
    public function record(CaseRecord $case, string $passType, array $output, string $model = 'anchor-stub-v1'): AiPassResult
    {
        $result = $case->aiPassResults()->create([
            'pass_type' => $passType,
            'raw_output' => $output,
            'model_identifier' => $model,
        ]);
        if ($passType === 'legal_classification') {
            $this->syncCitations($case);
        }
        $this->combination->recompute($case);

        return $result;
    }

    private function syncCitations(CaseRecord $case): void
    {
        $legal = $case->aiPassResults()->where('pass_type', 'legal_classification')->latest('id')->first();
        foreach ((array) data_get($legal?->raw_output, 'cited_pasal', []) as $citation) {
            $entry = LegalCorpusEntry::query()->where('law_name', data_get($citation, 'law_name'))
                ->where('pasal_reference', data_get($citation, 'pasal_reference'))->where('status', 'active')->first();
            if ($entry !== null) {
                $case->citations()->syncWithoutDetaching([$entry->id]);
            }
        }
    }
}
