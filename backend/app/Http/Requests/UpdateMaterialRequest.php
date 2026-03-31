<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|unique:materials,code,' . $this->material,
            'name' => 'sometimes|string|max:255',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'unit_id' => 'sometimes|uuid|exists:units,id',
            'metadata' => 'nullable|array',
        ];
    }
}
