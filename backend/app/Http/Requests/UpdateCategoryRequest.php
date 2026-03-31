<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|unique:categories,code,' . $this->category,
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:material,product,bom,other',
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'metadata' => 'nullable|array',
        ];
    }
}
