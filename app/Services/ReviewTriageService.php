<?php

namespace App\Services;

use App\Models\CaseRecord;
use App\Models\Review;

class ReviewTriageService
{
    /** @return array{confidence: float|null, flag: string, suggested_approval: array<string, mixed>|null} */
    public function assess(CaseRecord $case): array
    {
        $candidates = Review::query()->where('action', 'approve')->whereNull('edited_output')
            ->with(['caseRecord.severityScore', 'caseRecord.evidenceItems', 'caseRecord.citations'])->get();
        $best = null;
        $confidence = null;

        foreach ($candidates as $review) {
            $candidate = $review->caseRecord;
            if ($candidate === null || $candidate->id === $case->id) {
                continue;
            }
            $score = $this->similarity($case, $candidate);
            if ($confidence === null || $score > $confidence) {
                $confidence = $score;
                $best = $candidate;
            }
        }

        if ($confidence === null) {
            return ['confidence' => null, 'flag' => 'no_historical_match', 'suggested_approval' => null];
        }

        $flag = $confidence < config('anchor.triage.novel_pattern_threshold') ? 'novel_or_ambiguous' : 'historical_pattern_match';
        $suggestion = null;
        if ($confidence >= config('anchor.triage.one_click_threshold')) {
            $suggestion = [
                'action' => 'approve',
                'source_case_id' => $best->id,
                'confidence' => $confidence,
                'requires_explicit_reviewer_confirmation' => true,
            ];
        }

        return ['confidence' => $confidence, 'flag' => $flag, 'suggested_approval' => $suggestion];
    }

    private function similarity(CaseRecord $current, CaseRecord $historical): float
    {
        $currentLegal = data_get($current->aiPassResults()->where('pass_type', 'legal_classification')->latest('id')->first()?->raw_output, 'classification');
        $historicalLegal = data_get($historical->aiPassResults()->where('pass_type', 'legal_classification')->latest('id')->first()?->raw_output, 'classification');
        $legal = $currentLegal !== null && $currentLegal === $historicalLegal ? 0.45 : 0.0;
        $currentHostility = (int) data_get($current->aiPassResults()->where('pass_type', 'hostility_score')->latest('id')->first()?->raw_output, 'score', 0);
        $historicalHostility = (int) data_get($historical->aiPassResults()->where('pass_type', 'hostility_score')->latest('id')->first()?->raw_output, 'score', 0);
        $hostility = 0.25 * (1 - min(100, abs($currentHostility - $historicalHostility)) / 100);
        $currentTypes = $current->evidenceItems->pluck('type')->unique()->values()->all();
        $historicalTypes = $historical->evidenceItems->pluck('type')->unique()->values()->all();
        $union = array_unique(array_merge($currentTypes, $historicalTypes));
        $evidence = count($union) === 0 ? 0.20 : 0.20 * (count(array_intersect($currentTypes, $historicalTypes)) / count($union));
        $severity = $current->severityScore?->computed_severity === $historical->severityScore?->computed_severity ? 0.10 : 0.0;

        return round($legal + $hostility + $evidence + $severity, 4);
    }
}
