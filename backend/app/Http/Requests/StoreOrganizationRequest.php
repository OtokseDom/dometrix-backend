<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // implement auth later
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:organizations,code',
            'timezone' => 'sometimes|string|max:50',
            'currency' => 'sometimes|string|max:10',
            'metadata' => 'sometimes|array',
        ];
    }
}
