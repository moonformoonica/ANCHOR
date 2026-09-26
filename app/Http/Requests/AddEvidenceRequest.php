<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['evidence' => ['required', 'array', 'min:1', 'max:50'], 'evidence.*.type' => ['required', 'in:link,screenshot'], 'evidence.*.url_or_file_ref' => ['required', 'string', 'max:2048'], 'evidence.*.timeline_at' => ['nullable', 'date']];
    }
}
