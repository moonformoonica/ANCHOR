<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'edit', 'reject'])],
            'edited_output' => ['required_if:action,edit', 'array'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'rejection_route' => ['required_if:action,reject', 'nullable', Rule::in(['reclassify_pending', 'manual_handling'])],
        ];
    }
}
