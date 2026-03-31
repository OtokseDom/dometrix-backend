<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:taxes,code',
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
