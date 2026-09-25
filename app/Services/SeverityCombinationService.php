<?php

namespace App\Services;

use App\Models\CaseRecord;
use App\Models\SeverityScore;

class SeverityCombinationService
{
    public function recompute(CaseRecord $case): ?SeverityScore
    {
        $legal = $this->latest($case, 'legal_classification');
        $hostility = $this->latest($case, 'hostility_score');

        if ($legal === null || $hostility === null) {
            return null;
        }

        $count = $case->evidenceItems()->count();
        $timeline = $case->evidenceItems()->whereNotNull('timeline_at')->orderBy('timeline_at')->get(['timeline_at']);
        $spanDays = $timeline->count() > 1 ? $timeline->first()->timeline_at->diffInDays($timeline->last()->timeline_at) : 0;
        $breadth = min(100, ($count * 20) + min(40, $spanDays));
        $intensity = min(100, max(0, (int) data_get($hostility->raw_output, 'score', 0)));
        $legalSignal = min(100, max(0, (int) data_get($legal->raw_output, 'severity_signal', 0)));
        $syncFlag = abs($legalSignal - $intensity) >= config('anchor.severity.disagreement_threshold');
        $combined = (int) round(($breadth + $intensity) / 2);
        $severity = match (true) {
            $combined >= 75 => 'critical',
            $combined >= 50 => 'high',
            $combined >= 25 => 'medium',
            default => 'low',
        };

        $score = SeverityScore::updateOrCreate(['case_id' => $case->id], [
            'breadth_score' => $breadth,
            'intensity_score' => $intensity,
            'computed_severity' => $severity,
            'sync_flag' => $syncFlag,
            'computed_at' => now(),
        ]);

        $case->update(['status' => 'pending_review']);

        return $score;
    }

    private function latest(CaseRecord $case, string $passType): ?\App\Models\AiPassResult
    {
        return $case->aiPassResults()->where('pass_type', $passType)->latest('id')->first();
    }
}
