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

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
        ]);

        $api = app(MyBankApi::class);
        $res = $api->login($data['username'], $data['password']);

        if (!$res['ok']) {
            $msg = $res['data']['detail'] ?? $res['data']['message'] ?? 'Credenciales inválidas.';
            return back()->withInput()->withErrors(['login' => $msg]);
        }

        $access  = $res['data']['access_token']  ?? null;
        $refresh = $res['data']['refresh_token'] ?? null;

        if (!$access || !$refresh) {
            return back()->withInput()->withErrors(['login' => 'Respuesta inválida del servidor (tokens).']);
        }

        session([
            'mybank_access_token'     => $access,
            'mybank_refresh_token'    => $refresh,
            'mybank_token_expires_at' => $api->jwtExp($access), // ✅ int timestamp
        ]);

        $me = $api->me($access);
        if ($me['ok']) {
            session(['mybank_user' => $me['data']]);
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}


