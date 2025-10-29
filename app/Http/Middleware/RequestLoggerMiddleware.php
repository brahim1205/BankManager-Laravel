<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $ip = $request->ip();
        $userId = $user ? $user->id : 'guest';

        // Clé de cache pour compter les requêtes par jour
        $cacheKey = "requests:{$userId}:{$ip}:" . now()->format('Y-m-d');

        // Incrémenter le compteur
        $requestCount = Cache::increment($cacheKey);

        // Définir l'expiration à la fin de la journée
        Cache::put($cacheKey, $requestCount, now()->endOfDay());

        // Logger si plus de 10 requêtes par jour
        if ($requestCount > 10) {
            Log::warning("Utilisateur {$userId} ({$ip}) a fait {$requestCount} requêtes aujourd'hui", [
                'user_id' => $userId,
                'ip' => $ip,
                'request_count' => $requestCount,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $next($request);
    }
}
