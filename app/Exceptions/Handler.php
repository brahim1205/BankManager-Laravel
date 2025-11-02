<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
            // Logging par défaut
        });

        // Réponses JSON propres pour API
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Validation errors
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Auth errors
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated',
                    ], 401);
                }

                $status = 500;
                $message = 'Server Error';

                if ($e instanceof HttpExceptionInterface) {
                    $status = $e->getStatusCode();
                    $message = $e->getMessage() ?: Response::$statusTexts[$status] ?? 'Error';
                } elseif (config('app.debug')) {
                    $message = $e->getMessage();
                }

                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], $status);
            }
        });
    }
}
