<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage users') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$userId],
            'password' => ['sometimes', Password::defaults()],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
            'status' => ['sometimes', 'in:active,disabled'],
            'mfa_enabled' => ['boolean'],
        ];
    }
}
