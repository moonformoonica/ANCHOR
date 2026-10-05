<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\CaseRecord;
use App\Models\LegalCorpusEntry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuditCaseAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $corpus = $request->route('corpus');
        if (! $corpus instanceof LegalCorpusEntry && is_numeric($corpus)) {
            $corpus = LegalCorpusEntry::query()->find($corpus);
        }
        $corpusId = $corpus instanceof LegalCorpusEntry ? $corpus->id : null;
        $corpusBefore = $this->corpusSnapshot($corpus);

        $response = $next($request);
        $case = $request->attributes->get('anchor.case');
        if (! $case instanceof CaseRecord && $request->route('public_case_id') !== null) {
            $case = CaseRecord::query()->where('public_case_id', $request->route('public_case_id'))->first();
        }
        if (! $case instanceof CaseRecord && $request->route('case') instanceof CaseRecord) {
            $case = $request->route('case');
        }
        if (! $case instanceof CaseRecord && $request->route()?->getName() === 'victim.cases.store' && $response instanceof JsonResponse) {
            $publicCaseId = data_get($response->getData(true), 'public_case_id');
            $case = is_string($publicCaseId)
                ? CaseRecord::query()->where('public_case_id', $publicCaseId)->first()
                : null;
        }
        $user = $request->user();
        $actorType = $case instanceof CaseRecord && $user === null ? 'victim' : 'system';
        if ($user !== null) {
            $actorType = $user->hasRole('admin') ? 'admin' : 'reviewer';
        }
        $details = ['route' => $request->route()?->getName(), 'method' => $request->method(), 'response_status' => $response->getStatusCode()];
        $routeName = $request->route()?->getName();
        if (in_array($routeName, ['corpus.store', 'corpus.update'], true) && $response->isSuccessful()) {
            if ($routeName === 'corpus.store' && $response instanceof JsonResponse) {
                $corpusId = data_get($response->getData(true), 'data.id');
            }
            $updatedCorpus = $corpusId === null ? null : LegalCorpusEntry::query()->find($corpusId);
            if ($updatedCorpus !== null) {
                $details += [
                    'resource_type' => 'legal_corpus_entry',
                    'resource_id' => $updatedCorpus->id,
                    'changes' => [
                        'before' => $corpusBefore,
                        'after' => $this->corpusSnapshot($updatedCorpus),
                    ],
                    'change_reason' => $request->input('change_reason'),
                ];
            }
        }

        AuditLog::create([
            'actor_type' => $actorType,
            'actor_id' => $user?->id,
            'action' => sprintf('%s %s', $request->method(), $request->route()?->getName() ?? $request->path()),
            'case_id' => $case?->id,
            'details' => $details,
            'created_at' => now(),
        ]);

        return $response;
    }

    /** @return array<string, mixed>|null */
    private function corpusSnapshot(?LegalCorpusEntry $entry): ?array
    {
        return $entry?->only(['law_name', 'pasal_reference', 'status', 'last_verified_date', 'metadata']);
    }
}
