<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddEvidenceRequest;
use App\Http\Requests\CreateCaseRequest;
use App\Models\CaseNarrative;
use App\Models\CaseRecord;
use App\Services\CaseOutputService;
use App\Services\CaseProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VictimCaseController extends Controller
{
    public function store(CreateCaseRequest $request, CaseProcessingService $processing): JsonResponse
    {
        $token = Str::random(64);
        $case = DB::transaction(function () use ($request, $token) {
            $case = CaseRecord::create(['public_case_id' => 'AC-'.Str::upper(Str::random(12)), 'token_hash' => Hash::make($token), 'status' => 'intake']);
            CaseNarrative::create(['case_id' => $case->id, 'narrative_text' => $request->string('narrative'), 'submitted_at' => now()]);
            foreach ($request->input('evidence', []) as $item) {
                $case->evidenceItems()->create([...$item, 'submitted_at' => now()]);
            }
            return $case;
        });
        $processing->process($case);
        return response()->json(['public_case_id' => $case->public_case_id, 'secret_token' => $token, 'message' => 'Store this token now. It cannot be retrieved later.'], 201);
    }

    public function addEvidence(AddEvidenceRequest $request, string $publicCaseId, CaseProcessingService $processing): JsonResponse
    {
        /** @var CaseRecord $case */
        $case = $request->attributes->get('anchor.case');
        if (in_array($case->status, ['closed', 'manual_handling'], true)) {
            return response()->json(['message' => 'Evidence cannot be added to this case state.'], 422);
        }
        foreach ($request->input('evidence') as $item) {
            $case->evidenceItems()->create([...$item, 'submitted_at' => now()]);
        }
        $processing->process($case->fresh());
        return response()->json(['message' => 'Evidence added and case reprocessed.'], 201);
    }

    public function show(string $publicCaseId, \Illuminate\Http\Request $request, CaseOutputService $output): JsonResponse
    {
        /** @var CaseRecord $case */
        $case = $request->attributes->get('anchor.case');
        return response()->json(array_filter([
            'public_case_id' => $case->public_case_id,
            'status' => $case->status,
            'final_output' => $output->victimFinalOutput($case),
            'support' => $output->distressResponse($case),
        ], fn ($value) => $value !== null));
    }
}
