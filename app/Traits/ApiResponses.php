<?php
namespace App\Traits;

trait ApiResponses
{
    protected function success($message, $status = 200)
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    protected function ok($message)
    {
        return $this->success($message, 200);
    }
}