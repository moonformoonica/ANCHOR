<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCorpusRequest;
use App\Http\Requests\UpdateCorpusRequest;
use App\Models\LegalCorpusEntry;
use Illuminate\Http\JsonResponse;

class CorpusController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', LegalCorpusEntry::class);

        return response()->json(['data' => LegalCorpusEntry::query()->orderBy('law_name')->orderBy('pasal_reference')->get()]);
    }

    public function store(StoreCorpusRequest $request): JsonResponse
    {
        $this->authorize('create', LegalCorpusEntry::class);
        $data = $request->validated();
        unset($data['change_reason']);

        return response()->json(['data' => LegalCorpusEntry::create($data)], 201);
    }

    public function update(UpdateCorpusRequest $request, LegalCorpusEntry $corpus): JsonResponse
    {
        $this->authorize('update', $corpus);
        $data = $request->validated();
        unset($data['change_reason']);
        $corpus->update($data);

        return response()->json(['data' => $corpus]);
    }
}
