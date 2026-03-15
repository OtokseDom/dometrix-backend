<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => 'sometimes|string|unique:roles,name,' . $roleId . ',id',
            'permissions' => 'sometimes|array',
        ];
    }
}
