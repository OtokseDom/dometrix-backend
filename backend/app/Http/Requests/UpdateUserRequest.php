<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId . ',id',
            'password' => 'sometimes|string|min:8',
            'role_id' => 'nullable|uuid|exists:roles,id',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'metadata' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
