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

        $result = $api->login($data['username'], $data['password']);

        if (!$result['ok']) {
            return back()
                ->withInput()
                ->withErrors(['login' => 'Credenciales inválidas o API no disponible.']);
        }

        $tokens = $result['data'];

        session([
            'mybank_access_token' => $tokens['access_token'] ?? null,
            'mybank_refresh_token' => $tokens['refresh_token'] ?? null,
        ]);

        // Validar /me
        $me = $api->me(session('mybank_access_token'));

        if (!$me['ok']) {
            session()->forget(['mybank_access_token','mybank_refresh_token']);
            return back()
                ->withInput()
                ->withErrors(['login' => 'Login OK pero no se pudo validar /auth/me.']);
        }

        session(['mybank_user' => $me['data']]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        session()->forget(['mybank_access_token','mybank_refresh_token','mybank_user']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

