<?php

namespace App\Helpers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
        if ($data instanceof ResourceCollection) {
            // For collections, extract just the array, don't wrap again
            $resolved = $data->response()->getData(true);
            $data = $resolved['data'] ?? [];
        } elseif ($data instanceof JsonResource) {
            // For single resources, extract the resource data directly
            $resolved = $data->response()->getData(true);
            $data = $resolved['data'] ?? $resolved;
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
