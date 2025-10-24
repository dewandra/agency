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
use Illuminate\Auth\Access\AuthorizationException;
use App\Helpers\ResponseHelper;

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
            return ResponseHelper::unauthorized('Authentication required. Please login to continue.');
        }

        // Authorization Exception (403)
        if ($exception instanceof AuthorizationException) {
            return ResponseHelper::forbidden(
                $exception->getMessage() ?: 'You do not have permission to perform this action.'
            );
        }

        // Validation Exception
        if ($exception instanceof ValidationException) {
            // Flatten errors to string format
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors[$field] = is_array($messages) ? $messages[0] : $messages;
            }
            
            return response()->json([
                'status' => 422,
                'error' => 'Validation Error',
                'details' => $errors,
            ], 422);
        }

        // Model Not Found Exception
        if ($exception instanceof ModelNotFoundException) {
            $model = class_basename($exception->getModel());
            return ResponseHelper::notFound($model);
        }

        // Not Found Exception (404)
        if ($exception instanceof NotFoundHttpException) {
            return ResponseHelper::error(
                'Not Found',
                404,
                null,
                'The requested endpoint does not exist.'
            );
        }

        // Method Not Allowed Exception (405)
        if ($exception instanceof MethodNotAllowedHttpException) {
            return ResponseHelper::error(
                'Method Not Allowed',
                405,
                null,
                'The HTTP method used is not allowed for this endpoint.'
            );
        }

        // HTTP Exception
        if ($exception instanceof HttpException) {
            return ResponseHelper::error(
                'HTTP Exception',
                $exception->getStatusCode(),
                null,
                $exception->getMessage() ?: 'An HTTP error occurred.'
            );
        }

        // JWT Exceptions
        if ($exception instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
            return ResponseHelper::unauthorized('Your session has expired. Please login again.');
        }

        if ($exception instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
            return ResponseHelper::unauthorized('Invalid authentication token.');
        }

        if ($exception instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException) {
            return ResponseHelper::unauthorized('Token error: ' . $exception->getMessage());
        }

        // Database Exceptions
        if ($exception instanceof \Illuminate\Database\QueryException) {
            // Don't expose SQL errors in production
            if (config('app.debug')) {
                return ResponseHelper::error(
                    'Database Error',
                    500,
                    ['sql_error' => $exception->getMessage()],
                    'A database error occurred.'
                );
            }
            
            return ResponseHelper::error(
                'Database Error',
                500,
                null,
                'A database error occurred. Please try again later.'
            );
        }

        // Default Server Error (500)
        $message = 'An unexpected error occurred.';
        $details = null;

        if (config('app.debug')) {
            $message = $exception->getMessage();
            $details = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->map(function ($trace) {
                    return [
                        'file' => $trace['file'] ?? 'unknown',
                        'line' => $trace['line'] ?? 'unknown',
                        'function' => $trace['function'] ?? 'unknown',
                    ];
                })->toArray(),
            ];
        }

        return ResponseHelper::error(
            'Internal Server Error',
            500,
            $details,
            $message
        );
    }
}