<?php

namespace App\Services;

use App\Contracts\DistressCheckServiceInterface;
use App\Models\CaseRecord;

class StubDistressCheckService implements DistressCheckServiceInterface
{
    public function check(CaseRecord $case, ?string $reviewContext = null): array
    {
        return [
            'distress_flag' => false,
            'support_resources' => [
                ['name' => 'SEJIWA', 'contact' => '119 ext. 8'],
                ['name' => 'Emergency services', 'contact' => '112'],
            ],
        ];
    }
}
