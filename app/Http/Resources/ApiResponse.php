<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Consistent JSON envelope for API responses.
 *
 * shape: { "data": ..., "message": string|null, "meta": {...} }
 *
 * Not a JsonResource itself — it builds a JsonResponse so callers can pass
 * plain arrays, model arrays, or JsonResource instances uniformly.
 */
class ApiResponse
{
    /**
     * @param  mixed  $data
     */
    public static function ok($data, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        $resolved = $data instanceof JsonResource
            ? $data->toArray(request())
            : $data;

        return response()->json([
            'data' => $resolved,
            'message' => $message,
            'meta' => $meta,
        ], $status);
    }
}
