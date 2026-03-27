<?php

namespace App\Helpers;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse
{
    public static function send(
        mixed $data = null,
        ?string $message = null,
        bool $success = true,
        int $code = 200,
        ?array $errors = null
    ) {
        // 🔥 KEY FIX: resolve resource properly
        if ($data instanceof JsonResource) {
            $data = $data->response()->getData(true);
        }
        $response = [
            'success' => $success,
            'message' => $message ?? ($success ? 'Request successful' : 'Request failed'),
            'data' => $data
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
