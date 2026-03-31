<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|unique:products,code,' . $this->product,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'unit_id' => 'sometimes|uuid|exists:units,id',
            'metadata' => 'nullable|array',
        ];
    }
}
