<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->route('organization');

        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:organizations,code,' . $organizationId . ',id',
            'timezone' => 'sometimes|string|max:50',
            'currency' => 'sometimes|string|max:10',
            'metadata' => 'sometimes|array',
        ];
    }
}
