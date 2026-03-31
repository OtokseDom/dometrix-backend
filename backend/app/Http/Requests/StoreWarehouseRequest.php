<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'type' => 'sometimes|string|max:50',
            'location' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'manager_user_id' => 'nullable|uuid|exists:users,id',
            'metadata' => 'nullable|array',
        ];
    }
}
