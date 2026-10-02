<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnlyMiddleware
{
    /**
     * Valida estrictamente que el usuario tenga privilegios de Administrador.
     * Si no es administrador, bloquea el acceso con HTTP 403.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Verificar si hay usuario autenticado mediante Auth nativo de Laravel
        if (auth()->check()) {
            $user = auth()->user();
            if (($user->role_id ?? null) == 1 || strtoupper($user->role ?? '') === 'ADMIN') {
                return $next($request);
            }
        }

        // 2. Verificar si hay sesión activa de MyBank
        $sessionUser = session('mybank_user');
        if ($sessionUser) {
            $role = strtoupper($sessionUser['role'] ?? '');
            $roleId = $sessionUser['role_id'] ?? null;

            if ($role === 'ADMIN' || $roleId == 1 || $role === '1') {
                return $next($request);
            }
        }

        // Si es petición AJAX/JSON
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Solo administradores pueden realizar esta acción.'], 403);
        }

        // Si es usuario normal, se le bloquea el paso a las zonas de admin
        abort(403, 'Solo administradores pueden realizar esta acción.');
    }
}
