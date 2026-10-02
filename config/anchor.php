<?php

return [
    'victim_token_ttl_days' => (int) env('ANCHOR_VICTIM_TOKEN_TTL_DAYS', 30),
    'triage' => [
        'one_click_threshold' => (float) env('ANCHOR_ONE_CLICK_CONFIDENCE', 0.90),
        'novel_pattern_threshold' => (float) env('ANCHOR_NOVEL_PATTERN_CONFIDENCE', 0.55),
    ],
    'severity' => [
        'disagreement_threshold' => (int) env('ANCHOR_SEVERITY_DISAGREEMENT_THRESHOLD', 35),
    ],
];
