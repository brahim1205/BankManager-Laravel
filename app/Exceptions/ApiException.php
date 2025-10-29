<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponseTrait;

class ApiException extends Exception
{
    use ApiResponseTrait;

    protected $statusCode;
    protected $errors;

    public function __construct(
        string $message = 'Une erreur est survenue',
        int $statusCode = 400,
        array $errors = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        return $this->errorResponse(
            $this->getMessage(),
            $this->statusCode,
            $this->errors
        );
    }

    public static function notFound(string $resource = 'Ressource'): self
    {
        return new self(
            "{$resource} introuvable",
            404
        );
    }

    public static function unauthorized(string $message = 'Accès non autorisé'): self
    {
        return new self($message, 403);
    }

    public static function validationError(array $errors): self
    {
        return new self(
            'Données de validation invalides',
            422,
            $errors
        );
    }

    public static function compteBloque(?string $motif = null): self
    {
        $message = 'Ce compte est bloqué';
        if ($motif) {
            $message .= ": {$motif}";
        }

        return new self($message, 423);
    }

    public static function soldeInsuffisant(float $solde, float $montant): self
    {
        return new self(
            "Solde insuffisant. Solde actuel: {$solde}, Montant demandé: {$montant}",
            402
        );
    }
}
