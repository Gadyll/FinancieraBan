<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * MyBankAdminOnly — solo ADMIN puede pasar.
 * Usado en rutas exclusivas: gestión de usuarios, configuraciones.
 * Los USER ven un mensaje de acceso denegado.
 */
class MyBankAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('mybank_user');
        $role = $user['role'] ?? null;

        if ($role !== 'ADMIN') {
            // Si es AJAX/JSON, retornar 403
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Acceso denegado. Solo administradores.'], 403);
            }

            return redirect()
                ->route('dashboard')
                ->withErrors(['auth' => 'Acceso denegado. Solo el administrador puede gestionar usuarios.']);
        }

        return $next($request);
    }
}
