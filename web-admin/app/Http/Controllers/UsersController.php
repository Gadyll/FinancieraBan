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
            // Tu API devuelve lista directa (list[UserOut])
            $users = is_array($res['data']) ? $res['data'] : ($res['data']['data'] ?? []);
        }

        // ✅ CONSERVAMOS tu ruta existente auth/users/index.blade.php
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

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'email'    => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ], [
            'username.required' => 'El username es obligatorio.',
            'email.required'    => 'El correo es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener mínimo 8 caracteres.',
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

        // ✅ Limpia el form después de crear
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

        $res = $api->toggleUserActive($accessToken, (int) $userId);

        if (!$res['ok']) {
            return redirect()
                ->route('users.index')
                ->withErrors(['users' => "No se pudo cambiar estado: ({$res['status']}) " . json_encode($res['data'])]);
        }

        return redirect()->route('users.index')->with('ok', 'Estado actualizado.');
    }

    public function destroy(string $userId, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $res = $api->deleteUser($accessToken, (int) $userId);

        if (!$res['ok']) {
            // ✅ 409 = bloqueado por historial
            if ((int) $res['status'] === 409) {
                $detail = $res['data']['detail'] ?? 'No se puede eliminar: tiene historial. Solo desactivar.';
                return redirect()->route('users.index')->withErrors(['users' => $detail]);
            }

            return redirect()
                ->route('users.index')
                ->withErrors(['users' => "No se pudo eliminar: ({$res['status']}) " . json_encode($res['data'])]);
        }

        return redirect()->route('users.index')->with('ok', 'Cobrador eliminado definitivamente.');
    }
}





