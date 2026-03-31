<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:materials,code',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'metadata' => 'nullable|array',
        ];
    }
}
