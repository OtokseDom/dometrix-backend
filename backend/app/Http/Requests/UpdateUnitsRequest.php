<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uomId = $this->route('unit');

        return [
            'code' => 'sometimes|string|unique:units,code,'.$uomId.',id',
            'name' => 'sometimes|string',
            'type' => 'required|string',
            'metadata' => 'sometimes|array'
        ];
    }
}
