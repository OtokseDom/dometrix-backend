<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|unique:warehouses,code,' . $this->warehouse,
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:50',
            'location' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'manager_user_id' => 'nullable|uuid|exists:users,id',
            'metadata' => 'nullable|array',
        ];
    }
}
