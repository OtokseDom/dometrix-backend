<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|unique:taxes,code,' . $this->tax,
            'name' => 'sometimes|string|max:255',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
