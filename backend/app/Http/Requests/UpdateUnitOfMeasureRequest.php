<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uomId = $this->route('unit_of_measure');

        return [
            'code' => 'sometimes|string|unique:unit_of_measures,code,' . $uomId . ',id',
            'name' => 'sometimes|string',
            'metadata' => 'sometimes|array'
        ];
    }
}
