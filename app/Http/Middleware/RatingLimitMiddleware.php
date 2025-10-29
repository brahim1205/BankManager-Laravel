<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RatingLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ip = $request->ip();
        $route = $request->route()?->getName();
        $method = $request->method();
        $path = $request->path();

        // Log les utilisateurs qui atteignent les limites de taux
        Log::warning('Limite de taux atteinte', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip' => $ip,
            'route' => $route,
            'method' => $method,
            'path' => $path,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        return $next($request);
    }
}
