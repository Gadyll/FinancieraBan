<?php

namespace App\Http\Middleware;

use App\Services\MyBankApi;
use Closure;
use Illuminate\Http\Request;

class MyBankAuth
{
    public function handle(Request $request, Closure $next)
    {
        $access  = session('mybank_access_token');
        $refresh = session('mybank_refresh_token');

        if (!$access || !$refresh) {
            $this->flushSession();
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        // ✅ exp guardado como int timestamp
        $exp = session('mybank_token_expires_at');

        // si expira (margen 15s) intentamos refresh ANTES de llamar /me
        if (is_numeric($exp) && time() >= ((int)$exp - 15)) {
            $api = app(MyBankApi::class);

            $ref = $api->refresh($refresh);
            if (!$ref['ok']) {
                $this->flushSession();
                return redirect()->route('login')->withErrors(['login' => 'Sesión expirada. Inicia sesión nuevamente.']);
            }

            $newAccess  = $ref['data']['access_token'] ?? null;
            $newRefresh = $ref['data']['refresh_token'] ?? null;

            if (!$newAccess || !$newRefresh) {
                $this->flushSession();
                return redirect()->route('login')->withErrors(['login' => 'Refresh inválido.']);
            }

            session([
                'mybank_access_token'     => $newAccess,
                'mybank_refresh_token'    => $newRefresh,
                'mybank_token_expires_at' => $api->jwtExp($newAccess),
            ]);

            $access = $newAccess;
        }

        // Validar /me
        $api = app(MyBankApi::class);
        $me = $api->me($access);

        if (!$me['ok']) {
            // Si access falló, intentamos refresh 1 vez
            $ref = $api->refresh($refresh);
            if (!$ref['ok']) {
                $this->flushSession();
                return redirect()->route('login')->withErrors(['login' => 'Sesión inválida. Inicia sesión nuevamente.']);
            }

            $newAccess  = $ref['data']['access_token'] ?? null;
            $newRefresh = $ref['data']['refresh_token'] ?? null;

            if (!$newAccess || !$newRefresh) {
                $this->flushSession();
                return redirect()->route('login')->withErrors(['login' => 'Refresh inválido.']);
            }

            session([
                'mybank_access_token'     => $newAccess,
                'mybank_refresh_token'    => $newRefresh,
                'mybank_token_expires_at' => $api->jwtExp($newAccess),
            ]);

            $me = $api->me($newAccess);
            if (!$me['ok']) {
                $this->flushSession();
                return redirect()->route('login')->withErrors(['login' => 'No se pudo validar la sesión.']);
            }
        }

        session(['mybank_user' => $me['data']]);

        return $next($request);
    }

    private function flushSession(): void
    {
        session()->forget([
            'mybank_access_token',
            'mybank_refresh_token',
            'mybank_token_expires_at',
            'mybank_user',
        ]);
    }
}




