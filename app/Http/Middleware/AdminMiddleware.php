<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Verifica que el usuario autenticado sea admin.
     * El rol admin tiene id = 1 en la tabla roles.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        // No autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        // No es admin
        if ($user->role_id != 1) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción',
            ], 403);
        }

        return $next($request);
    }
}