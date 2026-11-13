<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Vérifier que l'utilisateur connecté possède l'un des rôles autorisés.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Authentification requise.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        foreach ($roles as $allowed) {
            if ($allowed === 'cabinet' && $user->isCabinet()) {
                return $next($request);
            }

            if ($user->role?->value === $allowed) {
                return $next($request);
            }
        }

        abort(403, 'Vous n\'êtes pas autorisé à accéder à cet espace.');
    }
}
