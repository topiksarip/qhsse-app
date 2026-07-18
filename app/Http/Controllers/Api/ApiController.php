<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Base controller for /api/v1 JSON endpoints.
 * Subclasses return ApiResponse envelopes.
 */
class ApiController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function ok($data, ?string $message = null, array $meta = []): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::ok($data, $message, $meta);
    }
}
