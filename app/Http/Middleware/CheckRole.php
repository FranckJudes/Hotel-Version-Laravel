<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Non authentifié',
                'success' => false
            ], 401);
        }

        $user = Auth::user();

        // S'assurer que $user->role est bien défini
        if (!$user->role) {
            return response()->json([
                'message' => 'Rôle utilisateur non défini',
                'success' => false
            ], 403);
        }

        // Comparer la valeur de l'enum avec les rôles autorisés
        foreach ($roles as $role) {
            if ($user->role->value === $role) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Accès non autorisé. Vous n\'avez pas les permissions nécessaires.',
            'success' => false
        ], 403);
    }
}