<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'status' => ['required', 'string'],
        ];
    }
}
