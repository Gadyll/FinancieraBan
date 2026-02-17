<?php

namespace App\Http\Middleware;

use App\Services\MyBankApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MyBankAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $access = session('mybank_access_token');
        $refresh = session('mybank_refresh_token');

        if (!$access || !$refresh) {
            $this->flushSession();
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        // Si ya está expirado (por exp del JWT), intentamos refresh ANTES de llamar a /me
        $exp = session('mybank_token_expires_at');
        if ($exp && time() >= ((int)$exp - 15)) { // 15s de margen
            $api = app(MyBankApi::class);
            $ref = $api->refresh($refresh);

            if (!$ref['ok']) {
                $this->flushSession();
                return redirect()->route('login')->withErrors(['login' => 'Sesión expirada. Inicia sesión nuevamente.']);
            }

            session([
                'mybank_access_token' => $ref['data']['access_token'] ?? null,
                'mybank_refresh_token' => $ref['data']['refresh_token'] ?? null,
                'mybank_token_expires_at' => $api->jwtExp($ref['data']['access_token'] ?? null),
            ]);

            $access = session('mybank_access_token');
        }

        // Validar contra API (/auth/me). Si 401, intenta refresh 1 vez
        $api = app(MyBankApi::class);
        $me = $api->me($access);

        if ($me['ok']) {
            session(['mybank_user' => $me['data']]);
            return $next($request);
        }

        if (($me['status'] ?? null) === 401) {
            $ref = $api->refresh($refresh);

            if (!$ref['ok']) {
                $this->flushSession();
                return redirect()->route('login')->withErrors(['login' => 'Sesión expirada. Inicia sesión nuevamente.']);
            }

            session([
                'mybank_access_token' => $ref['data']['access_token'] ?? null,
                'mybank_refresh_token' => $ref['data']['refresh_token'] ?? null,
                'mybank_token_expires_at' => $api->jwtExp($ref['data']['access_token'] ?? null),
            ]);

            $me2 = $api->me(session('mybank_access_token'));
            if (!$me2['ok']) {
                $this->flushSession();
                return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
            }

            session(['mybank_user' => $me2['data']]);
            return $next($request);
        }

        // Cualquier otro error
        return redirect()->route('login')->withErrors(['login' => 'No se pudo validar sesión contra la API.']);
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



