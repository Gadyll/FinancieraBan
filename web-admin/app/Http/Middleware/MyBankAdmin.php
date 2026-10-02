<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * MyBankAuth — verifica que haya una sesión de MyBank válida.
 * Acepta tanto ADMIN como USER.
 * Si el rol es USER, no puede acceder a rutas exclusivas de admin
 * (esas están protegidas con middleware 'mybank.admin_only').
 */
class MyBankAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('mybank_user');

        // Sin sesión → login
        if (!$user) {
            return redirect()->route('login');
        }

        $role = $user['role'] ?? null;

        // Solo ADMIN y USER pueden acceder al panel web
        if (!in_array($role, ['ADMIN', 'USER'], true)) {
            session()->forget([
                'mybank_user',
                'mybank_access_token',
                'mybank_refresh_token',
            ]);

            return redirect()
                ->route('login')
                ->withErrors([
                    'auth' => 'Acceso denegado. Tu cuenta no tiene permisos para el panel web.',
                ]);
        }

        return $next($request);
    }
}
