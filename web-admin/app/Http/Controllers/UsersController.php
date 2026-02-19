<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyBankApi;

class UsersController extends Controller
{
    public function index(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $error = null;
        $users = [];

        $res = $api->users($accessToken);

        if (!$res['ok']) {
            $error = "No se pudieron cargar usuarios: ({$res['status']}) " . json_encode($res['data']);
        } else {
            $users = is_array($res['data']) ? $res['data'] : ($res['data']['data'] ?? []);
        }

        // 🔥 IMPORTANTE: tu vista está en auth/users
        return view('auth.users.index', [
            'users' => $users,
            'error' => $error,
            'clearUserForm' => (bool) session('clear_user_form', false),
        ]);
    }

    public function store(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        // ✅ Validación estricta contraseña
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'email'    => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'regex:/[A-Z]/',           // mayúscula
                'regex:/[0-9]/',           // número
                'regex:/[^A-Za-z0-9]/',    // especial
            ],
        ], [
            'password.regex' => 'La contraseña debe incluir mayúscula, número y caracter especial.',
        ]);

        $payload = [
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'role'     => 'USER',
        ];

        $res = $api->createUser($accessToken, $payload);

        if (!$res['ok']) {
            $msg = $res['data']['detail'] ?? $res['data']['message'] ?? json_encode($res['data']);

            return redirect()
                ->route('users.index')
                ->withErrors(['users' => "No se pudo crear cobrador: ({$res['status']}) {$msg}"])
                ->withInput();
        }

        return redirect()
            ->route('users.index')
            ->with('ok', 'Cobrador creado correctamente.')
            ->with('clear_user_form', true);
    }

    public function toggleActive(string $userId, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $res = $api->toggleUserActive($accessToken, (int)$userId);

        if (!$res['ok']) {
            return redirect()
                ->route('users.index')
                ->withErrors(['users' => "No se pudo cambiar estado: ({$res['status']})"]);
        }

        return redirect()->route('users.index')->with('ok', 'Estado actualizado.');
    }

    public function destroy(string $userId, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $res = $api->deleteUser($accessToken, (int)$userId);

        if (!$res['ok']) {
            if ((int)$res['status'] === 409) {
                return redirect()
                    ->route('users.index')
                    ->withErrors(['users' => $res['data']['detail'] ?? 'Tiene historial, solo se puede desactivar.']);
            }

            return redirect()
                ->route('users.index')
                ->withErrors(['users' => "No se pudo eliminar."]);
        }

        return redirect()->route('users.index')->with('ok', 'Cobrador eliminado.');
    }
}





