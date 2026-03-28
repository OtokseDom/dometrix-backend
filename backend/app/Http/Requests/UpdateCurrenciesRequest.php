<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrenciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currencyId = $this->route('currency');

        return [
            'code' => 'sometimes|string|unique:currencies,code,'.$currencyId.',id',
            'name' => 'sometimes|string',
            'metadata' => 'sometimes|array'
        ];
    }
}
