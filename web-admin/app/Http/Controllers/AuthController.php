<?php

namespace App\Http\Controllers;

use App\Services\MyBankApi;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, MyBankApi $api)
    {
        $data = $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
        ]);

        $res = $api->login($data['username'], $data['password']);

        if (!$res['ok']) {
            return back()->withErrors(['login' => 'Credenciales inválidas o API no disponible.'])->withInput();
        }

        $access = $res['data']['access_token'] ?? null;
        $refresh = $res['data']['refresh_token'] ?? null;

        if (!$access || !$refresh) {
            return back()->withErrors(['login' => 'Respuesta inválida de la API.'])->withInput();
        }

        session([
            'mybank_access_token' => $access,
            'mybank_refresh_token' => $refresh,
            'mybank_token_expires_at' => $api->jwtExp($access),
        ]);

        // Guardar /me
        $me = $api->me($access);
        if ($me['ok']) {
            session(['mybank_user' => $me['data']]);
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        // Limpieza total
        $request->session()->forget([
            'mybank_access_token',
            'mybank_refresh_token',
            'mybank_token_expires_at',
            'mybank_user',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}


