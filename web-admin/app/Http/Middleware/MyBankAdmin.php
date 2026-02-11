<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MyBankAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('mybank_user');

        // No hay sesión → login
        if (!$user) {
            return redirect()->route('login');
        }

        // No es ADMIN → expulsar
        if (($user['role'] ?? null) !== 'ADMIN') {
            session()->forget([
                'mybank_user',
                'mybank_access_token',
                'mybank_refresh_token',
            ]);

            return redirect()
                ->route('login')
                ->withErrors([
                    'auth' => 'Acceso denegado: solo administradores',
                ]);
        }

        return $next($request);
    }
}
