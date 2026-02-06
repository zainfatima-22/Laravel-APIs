<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    //api responses
    public function ok(string $message = 'Success', array $data = []): JsonResponse
    {
        return $this->successResponse($message, $data, 200);
    }

    public function created(string $message = 'Resource created', array $data = []): JsonResponse
    {
        return $this->successResponse($message, $data, 201);
    }

    public function badRequest(string $message = 'Bad Request', array $data = []): JsonResponse
    {
        return $this->errorResponse($message, $data, 400);
    }

    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, [], 401);
    }

    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, [], 403);
    }

    public function notFound(string $message = 'Resource not found', array $data = []): JsonResponse
    {
        return $this->errorResponse($message, $data, 404);
    }

    public function unprocessable(string $message = 'Validation failed', array $data = []): JsonResponse
    {
        return $this->errorResponse($message, $data, 422);
    }

    protected function successResponse(string $message, array $data, int $statuscode): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $statuscode);
    }

    protected function errorResponse(string $message, array $data, int $statuscode): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $data,
        ], $statuscode);
    }
}