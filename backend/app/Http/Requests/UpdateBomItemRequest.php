<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBomItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_id' => 'nullable|uuid|exists:materials,id',
            'sub_product_id' => 'nullable|uuid|exists:products,id',
            'quantity' => 'sometimes|numeric|min:0.01',
            'unit_id' => 'sometimes|uuid|exists:units,id',
            'wastage_percent' => 'sometimes|numeric|min:0|max:100',
            'line_no' => 'sometimes|integer|min:1',
            'metadata' => 'nullable|array',
        ];
    }

    public function after()
    {
        return function ($validator) {
            if ($this->material_id && $this->sub_product_id) {
                $validator->errors()->add('material_id', 'Cannot specify both material_id and sub_product_id.');
            }
        };
    }
}
