<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle API exceptions
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return $this->handleApiException($request, $e);
            }
        });
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException($request, Throwable $exception): JsonResponse
    {
        // Authentication Exception
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login first.',
                'error' => 'authentication_required'
            ], 401);
        }

        // Validation Exception
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $exception->errors()
            ], 422);
        }

        // Model Not Found Exception
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
                'error' => 'not_found'
            ], 404);
        }

        // Not Found Exception
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found',
                'error' => 'endpoint_not_found'
            ], 404);
        }

        // Method Not Allowed Exception
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Method not allowed',
                'error' => 'method_not_allowed'
            ], 405);
        }

        // HTTP Exception
        if ($exception instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'HTTP error occurred',
                'error' => 'http_exception'
            ], $exception->getStatusCode());
        }

        // JWT Exceptions
        if ($exception instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
                'error' => 'token_expired'
            ], 401);
        }

        if ($exception instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid',
                'error' => 'token_invalid'
            ], 401);
        }

        if ($exception instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException) {
            return response()->json([
                'success' => false,
                'message' => 'Token error: ' . $exception->getMessage(),
                'error' => 'token_error'
            ], 401);
        }

        // Default Server Error
        $statusCode = 500;
        $message = 'Internal server error';
        
        // In development, show detailed error
        if (config('app.debug')) {
            $message = $exception->getMessage();
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'server_error',
            'trace' => config('app.debug') ? $exception->getTraceAsString() : null
        ], $statusCode);
    }
}