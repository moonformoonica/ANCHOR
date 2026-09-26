<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['sometimes', 'string', 'max:255'], 'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->route('user'))], 'password' => ['sometimes', 'string', 'min:12'], 'roles' => ['sometimes', 'array', 'min:1'], 'roles.*' => ['string', Rule::exists('roles', 'name')]];
    }
}
