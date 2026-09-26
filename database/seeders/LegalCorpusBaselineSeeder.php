<?php

namespace Database\Seeders;

use App\Models\LegalCorpusEntry;
use Illuminate\Database\Seeder;

class LegalCorpusBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['UU PDP No. 27/2022', 'Pasal 65 ayat (2)', 'primary_candidate'],
            ['UU PDP No. 27/2022', 'Pasal 67 ayat (2)', 'penalty_candidate'],
            ['UU ITE (amended by UU No. 1/2024)', 'Pasal 29 jo. Pasal 45B', 'direct-threat_candidate'],
            ['UU ITE (amended by UU No. 1/2024)', 'Pasal 27A jo. Pasal 45 ayat (4)', 'transition_review_required'],
            ['KUHP Nasional (UU No. 1/2023)', 'Pasal 433 jo. Pasal 441 ayat (1)', 'transition_review_required'],
            ['KUHP Nasional (UU No. 1/2023)', 'Pasal 448 ayat (1) huruf b', 'candidate_needs_expert_review'],
            ['POJK No. 40/2024', 'Pasal 161 ayat (1) huruf c jo. Pasal 164 ayat (1)', 'institutional_routing_candidate'],
        ];

        foreach ($entries as [$lawName, $reference, $reviewNote]) {
            LegalCorpusEntry::updateOrCreate(
                ['law_name' => $lawName, 'pasal_reference' => $reference],
                [
                    'status' => 'under_review',
                    'last_verified_date' => null,
                    'metadata' => [
                        'baseline' => 'week_1_v0.2',
                        'review_note' => $reviewNote,
                        'source_reviewed' => true,
                        'severity_weight' => null,
                    ],
                ]
            );
        }
    }
}