<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventClientAccess
{
    /**
     * Empêcher un compte client connecté d'accéder aux écrans internes du cabinet.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isClient()) {
            abort(403, 'Cet espace est réservé à l\'équipe du cabinet.');
        }

        return $next($request);
    }
}
