<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InternalAiPassResultRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['pass_type' => ['required', Rule::in(['legal_classification', 'hostility_score', 'distress_check'])], 'raw_output' => ['required', 'array'], 'model_identifier' => ['required', 'string', 'max:255']]; }
}
