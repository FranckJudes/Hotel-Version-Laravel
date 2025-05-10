<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || $user->role->value !== 'ADMIN') {
            return response()->json([
                'message' => 'Accès non autorisé. Vous n\'avez pas les permissions nécessaires.',
                'success' => false
            ], 403);
        }

        return $next($request);
    }
}