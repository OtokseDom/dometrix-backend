<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:categories,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:material,product,bom,other',
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'metadata' => 'nullable|array',
        ];
    }
}
