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

        // ── Sin tokens en sesión → login ──
        if (!$access || !$refresh) {
            $this->flushSession();
            return redirect()->route('login')
                ->withErrors(['login' => 'Sesión inválida. Por favor inicia sesión.']);
        }

        $exp = session('mybank_token_expires_at'); // int timestamp (unix)
        $api = app(MyBankApi::class);

        // ── Si el access token expira en < 60 segundos → renovar proactivamente ──
        if (is_numeric($exp) && time() >= ((int)$exp - 60)) {
            $ref = $api->refresh($refresh);

            if (!$ref['ok']) {
                $this->flushSession();
                return redirect()->route('login')
                    ->withErrors(['login' => 'Sesión expirada. Inicia sesión nuevamente.']);
            }

            $newAccess  = $ref['data']['access_token']  ?? null;
            $newRefresh = $ref['data']['refresh_token'] ?? null;

            if (!$newAccess || !$newRefresh) {
                $this->flushSession();
                return redirect()->route('login')
                    ->withErrors(['login' => 'Error al renovar sesión.']);
            }

            session([
                'mybank_access_token'     => $newAccess,
                'mybank_refresh_token'    => $newRefresh,
                'mybank_token_expires_at' => $api->jwtExp($newAccess),
            ]);

            $access = $newAccess;
        }

        // ── Verificar que haya datos de usuario en sesión ──
        // Solo llamamos /me si no hay datos de usuario cacheados o si el token cambió
        $cachedUser  = session('mybank_user');
        $cachedToken = session('mybank_user_token_hash');
        $accessHash  = substr($access, -16); // últimos 16 chars como fingerprint

        if (!$cachedUser || $cachedToken !== $accessHash) {
            $me = $api->me($access);

            if (!$me['ok']) {
                // Intento de refresh si /me falla (token revocado o corrupto)
                $ref = $api->refresh($refresh);
                if (!$ref['ok']) {
                    $this->flushSession();
                    return redirect()->route('login')
                        ->withErrors(['login' => 'Sesión inválida. Inicia sesión nuevamente.']);
                }

                $newAccess  = $ref['data']['access_token']  ?? null;
                $newRefresh = $ref['data']['refresh_token'] ?? null;

                if (!$newAccess || !$newRefresh) {
                    $this->flushSession();
                    return redirect()->route('login')
                        ->withErrors(['login' => 'Error al renovar sesión.']);
                }

                session([
                    'mybank_access_token'     => $newAccess,
                    'mybank_refresh_token'    => $newRefresh,
                    'mybank_token_expires_at' => $api->jwtExp($newAccess),
                ]);

                $me = $api->me($newAccess);
                if (!$me['ok']) {
                    $this->flushSession();
                    return redirect()->route('login')
                        ->withErrors(['login' => 'No se pudo validar la sesión.']);
                }

                $access     = $newAccess;
                $accessHash = substr($newAccess, -16);
            }

            session([
                'mybank_user'            => $me['data'],
                'mybank_user_token_hash' => $accessHash,
            ]);
        }

        return $next($request);
    }

    private function flushSession(): void
    {
        session()->forget([
            'mybank_access_token',
            'mybank_refresh_token',
            'mybank_token_expires_at',
            'mybank_user',
            'mybank_user_token_hash',
        ]);
    }
}
