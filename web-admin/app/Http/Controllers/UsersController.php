<?php

namespace App\Http\Controllers;

use App\Services\MyBankApi;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function index(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida. Inicia sesión de nuevo.']);
        }

        $error = null;

        // Traer lista de usuarios
        $resp = $api->users($accessToken, 0, 500);
        if (!$resp['ok']) {
            $error = "USERS FALLÓ ({$resp['status']}): " . json_encode($resp['data']);
            $users = [];
        } else {
            $users = $resp['data'];
        }

        // Orden: primero USER activos, luego USER inactivos, luego ADMIN
        $users = collect($users)->sortBy(function ($u) {
            $role = $u['role'] ?? '';
            $active = ($u['is_active'] ?? false) ? 0 : 1;
            return ($role === 'USER' ? 0 : 2) * 10 + $active;
        })->values()->all();

        return view('auth.users.index', [
            'users' => $users,
            'error' => $error,
        ]);
    }

    public function store(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'email'    => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:72'],
        ]);

        $resp = $api->createUser($accessToken, $data['username'], $data['password'], $data['email'] ?? null);

        if (!$resp['ok']) {
            return back()->withInput()->withErrors([
                'create_user' => "CREATE USER FALLÓ ({$resp['status']}): " . json_encode($resp['data']),
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Cobrador creado correctamente.');
    }

    public function toggleActive(int $id, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $resp = $api->toggleUserActive($accessToken, $id);

        if (!$resp['ok']) {
            return back()->withErrors([
                'toggle_user' => "TOGGLE FALLÓ ({$resp['status']}): " . json_encode($resp['data']),
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Estado actualizado.');
    }

    public function destroy(int $id, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $resp = $api->deleteUser($accessToken, $id);

        if (!$resp['ok']) {
            return back()->withErrors([
                'delete_user' => "DELETE FALLÓ ({$resp['status']}): " . json_encode($resp['data']),
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Usuario eliminado (inactivo).');
    }
}
