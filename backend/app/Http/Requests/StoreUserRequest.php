<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'nullable|uuid|exists:roles,id',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'metadata' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
