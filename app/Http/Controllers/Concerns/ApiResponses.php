<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    /**
     * Build a successful JSON response.
     */
    protected function success(array|Arrayable $data = [], int $status = 200): JsonResponse
    {
        $payload = $data instanceof Arrayable ? $data->toArray() : $data;
        return response()->json(['success' => true, 'data' => $payload], $status);
    }

    /**
     * Build a failure JSON response with a client error status (4xx).
     */
    protected function fail(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => $message,
                'details' => $errors ?: null,
            ],
        ], $status);
    }

    /**
     * Build a validation error response.
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->fail($message, $errors, 422);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->fail($message, [], 401);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->fail($message, [], 403);
    }

    protected function notFound(string $message = 'Not found'): JsonResponse
    {
        return $this->fail($message, [], 404);
    }

    protected function tooManyRequests(int $retryAfterSeconds): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => 'Too many requests',
                'retry_after' => $retryAfterSeconds,
            ],
        ], 429);
    }
}

