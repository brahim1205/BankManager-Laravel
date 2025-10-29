<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $user = $request->user();
        $method = $request->method();
        $path = $request->path();
        $ip = $request->ip();

        // Log avant traitement
        \Illuminate\Support\Facades\Log::info('Début de requête API', [
            'method' => $method,
            'path' => $path,
            'ip' => $ip,
            'user_id' => $user?->id,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Log après traitement
        \Illuminate\Support\Facades\Log::info('Fin de requête API', [
            'method' => $method,
            'path' => $path,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'ip' => $ip,
            'user_id' => $user?->id,
            'operation' => $this->getOperationName($method, $path),
            'resource' => $this->getResourceName($path),
            'host' => $request->getHost(),
            'timestamp' => now()->toISOString(),
        ]);

        return $response;
    }

    /**
     * Détermine le nom de l'opération
     */
    private function getOperationName(string $method, string $path): string
    {
        $operations = [
            'GET' => 'consultation',
            'POST' => 'création',
            'PUT' => 'modification',
            'PATCH' => 'modification_partielle',
            'DELETE' => 'suppression',
        ];

        return $operations[$method] ?? 'opération_inconnue';
    }

    /**
     * Détermine le nom de la ressource
     */
    private function getResourceName(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        $resource = $segments[1] ?? 'inconnue'; // api/v1/comptes -> comptes

        // Gestion des ressources imbriquées
        if (isset($segments[2]) && is_numeric($segments[2])) {
            $resource .= '_detail';
        }

        return $resource;
    }
}
