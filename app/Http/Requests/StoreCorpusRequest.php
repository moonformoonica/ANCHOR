<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCorpusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['law_name' => ['required', 'string', 'max:255'], 'pasal_reference' => ['required', 'string', 'max:255'], 'status' => ['required', Rule::in(['active', 'superseded', 'under_review'])], 'metadata' => ['nullable', 'array']];
    }
}
