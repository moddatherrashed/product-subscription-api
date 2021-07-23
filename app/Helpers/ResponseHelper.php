<?php

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    /**
     * @param $response
     * @return JsonResponse
     */
    public static function successResponse($response): JsonResponse
    {
        return response()->json([
                'success' => 1,
                'response' => $response
            ]
        );
    }

    /**
     * @param $errorMessage
     * @param $statusCode
     * @return JsonResponse
     */
    public static function errorResponse($errorMessage, $statusCode): JsonResponse
    {
        return response()->json([
                'success' => 0,
                'error' => [
                    'code' => $statusCode,
                    'message' => $errorMessage
                ]
            ]
        );
    }
}
