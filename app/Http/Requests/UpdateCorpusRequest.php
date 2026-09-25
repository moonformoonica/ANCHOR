<?php

namespace App\Http\Requests;

class UpdateCorpusRequest extends StoreCorpusRequest
{
    public function rules(): array
    {
        return ['law_name' => ['sometimes', 'string', 'max:255'], 'pasal_reference' => ['sometimes', 'string', 'max:255'], 'status' => ['sometimes', 'in:active,superseded,under_review'], 'metadata' => ['nullable', 'array']];
    }
}
