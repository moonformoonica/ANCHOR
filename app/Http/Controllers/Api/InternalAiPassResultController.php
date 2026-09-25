<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternalAiPassResultRequest;
use App\Models\CaseRecord;
use App\Services\CaseProcessingService;
use Illuminate\Http\JsonResponse;

class InternalAiPassResultController extends Controller
{
    public function store(InternalAiPassResultRequest $request, CaseRecord $case, CaseProcessingService $processing): JsonResponse
    {
        $data = $request->validated();
        $result = $processing->record($case, $data['pass_type'], $data['raw_output'], $data['model_identifier']);
        return response()->json(['data' => $result], 201);
    }
}
