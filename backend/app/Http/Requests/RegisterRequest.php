<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'password' => 'required|string|min:6|confirmed',
            'organization_name' => 'nullable|string|max:55|required_without:organization_code',
            'organization_code' => 'nullable|string|required_without:organization_name',
            'role_id' => 'nullable|uuid|exists:roles,id',
        ];
    }
}
