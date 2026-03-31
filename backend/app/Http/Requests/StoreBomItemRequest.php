<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBomItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bom_id' => 'required|uuid|exists:boms,id',
            'material_id' => 'nullable|uuid|exists:materials,id',
            'sub_product_id' => 'nullable|uuid|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_id' => 'required|uuid|exists:units,id',
            'wastage_percent' => 'sometimes|numeric|min:0|max:100',
            'line_no' => 'required|integer|min:1',
            'metadata' => 'nullable|array',
        ];
    }

    public function after()
    {
        return function ($validator) {
            if (!$this->material_id && !$this->sub_product_id) {
                $validator->errors()->add('material_id', 'Either material_id or sub_product_id must be provided.');
            }
            if ($this->material_id && $this->sub_product_id) {
                $validator->errors()->add('material_id', 'Cannot specify both material_id and sub_product_id.');
            }
        };
    }
}
