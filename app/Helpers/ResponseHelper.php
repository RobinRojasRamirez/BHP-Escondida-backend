<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function formatResponse($message, $status, $error, $data = null)
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
            'path' => request()->path(),
            'timestamp' => now(),
            'error' => $error,
            'data' => $data
        ], $status);
    }
}
