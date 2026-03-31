<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateBomCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => 'required|uuid|exists:organizations,id',
            'bom_id' => 'required|uuid|exists:boms,id',
            'quantity' => 'nullable|numeric|min:0.001',
            'effective_date' => 'nullable|date_format:Y-m-d',
            'costing_method' => 'nullable|in:weighted_average,fifo,lifo,standard',
            'include_product_cost' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.required' => 'Organization ID is required',
            'bom_id.required' => 'BOM ID is required',
            'quantity.min' => 'Quantity must be greater than 0',
            'effective_date.date_format' => 'Effective date must be in format YYYY-MM-DD',
        ];
    }
}
