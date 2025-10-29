<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Pour les requêtes API, ne pas rediriger, laisser le contrôleur gérer l'erreur
        if ($request->is('api/*')) {
            return null;
        }

        return route('login');
    }
}
