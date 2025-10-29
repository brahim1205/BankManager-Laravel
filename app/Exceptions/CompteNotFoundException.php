<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompteNotFoundException extends Exception
{
    protected $compteId;

    public function __construct($compteId, $message = "Le compte avec l'ID spécifié n'existe pas", $code = 0, \Throwable $previous = null)
    {
        $this->compteId = $compteId;
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'COMPTE_NOT_FOUND',
                'message' => $this->getMessage(),
                'details' => [
                    'compteId' => $this->compteId
                ],
                'timestamp' => now()->toISOString(),
                'path' => $request->getPathInfo(),
                'traceId' => $request->header('X-Request-ID', uniqid('req-', true))
            ]
        ], 404);
    }
}
