<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Pembentuk respons API konsisten: envelope { data, message, meta }.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function success(mixed $data = null, string $message = '', ?array $meta = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'meta' => $meta,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = ''): JsonResponse
    {
        return self::success($data, $message, status: 201);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function error(string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors ?: (object) [],
        ], $status);
    }
}
