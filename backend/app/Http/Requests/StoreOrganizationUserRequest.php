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
            // Note: organization_id comes from route parameter, not request body
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'status' => ['required', 'string'],
        ];
    }
}
