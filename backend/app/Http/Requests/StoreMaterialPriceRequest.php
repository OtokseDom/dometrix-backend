<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_id' => 'required|uuid|exists:materials,id',
            'price' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ];
    }
}
