<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    /**
     * Success response
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        $response = [
            'status' => $status,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Error response
     */
    public static function error(
        string $error,
        int $status = 500,
        $details = null,
        string $message = null
    ): JsonResponse {
        $response = [
            'status' => $status,
            'error' => $error,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($details !== null) {
            $response['details'] = $details;
        }

        if (config('app.debug') && $status === 500) {
            // Add trace in development
            $response['debug'] = [
                'file' => debug_backtrace()[0]['file'] ?? null,
                'line' => debug_backtrace()[0]['line'] ?? null,
            ];
        }

        return response()->json($response, $status);
    }

    /**
     * Validation error response (String format - no arrays)
     */
    public static function validationError($errors, string $message = 'Validation Error'): JsonResponse
    {
        $details = [];
        
        // Convert errors to simple string format
        if ($errors instanceof \Illuminate\Support\MessageBag) {
            $errorBag = $errors->toArray();
        } elseif (is_array($errors)) {
            $errorBag = $errors;
        } else {
            $errorBag = [];
        }
        
        // Flatten to string (take first error only)
        foreach ($errorBag as $field => $messages) {
            if (is_array($messages)) {
                $details[$field] = $messages[0]; // First error only
            } else {
                $details[$field] = $messages;
            }
        }
        
        return response()->json([
            'status' => 422,
            'error' => $message,
            'details' => $details,
        ], 422);
    }

    /**
     * Not found response
     */
    public static function notFound(string $resource = 'Resource'): JsonResponse
    {
        return response()->json([
            'status' => 404,
            'error' => 'Not Found',
            'message' => "{$resource} not found",
        ], 404);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'status' => 401,
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(string $message = 'Forbidden', $details = null): JsonResponse
    {
        $response = [
            'status' => 403,
            'error' => 'Forbidden',
            'message' => $message,
        ];

        if ($details) {
            $response['details'] = $details;
        }

        return response()->json($response, 403);
    }

    /**
     * Pagination response
     */
    public static function paginated($items, $pagination, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'message' => $message,
            'data' => $items,
            'pagination' => $pagination,
        ], 200);
    }

    /**
     * Created response
     */
    public static function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        $response = [
            'status' => 201,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, 201);
    }

    /**
     * No content response
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Deleted response
     */
    public static function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'message' => $message,
        ], 200);
    }
}