<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid|exists:products,id',
            'version' => 'required|string|max:50',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
