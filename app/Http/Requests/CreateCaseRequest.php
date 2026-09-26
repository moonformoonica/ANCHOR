<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'narrative' => ['required', 'string', 'max:50000'],
            'evidence' => ['nullable', 'array', 'max:50'],
            'evidence.*.type' => ['required_with:evidence', 'in:link,screenshot'],
            'evidence.*.url_or_file_ref' => ['required_with:evidence', 'string', 'max:2048'],
            'evidence.*.timeline_at' => ['nullable', 'date'],
        ];
    }
}
