<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
