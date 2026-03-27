<?php

namespace App\Helpers;

class ApiResponse
{
    public static function send(
        mixed $data = null,
        ?string $message = null,
        bool $success = true,
        int $code = 200,
        ?array $errors = null
    ) {
        $response = [
            'success' => $success,
            'message' => $message ?? ($success ? 'Request successful' : 'Request failed'),
            'data' => $data
        ];

        $response['errors'] = $errors ?? (object) [];

        return response()->json($response, $code);
    }
}
