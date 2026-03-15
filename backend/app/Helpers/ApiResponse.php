<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * Standardized API response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    public static function send($data = null, ?string $message = null, int $status = 200)
    {
        $response = [
            'status' => $status < 300 ? 'success' : 'error',
            'message' => $message ?? ($status < 300 ? 'Request successful' : 'Request failed'),
            'data' => $data,
        ];

        return response()->json($response, $status);
    }
}
