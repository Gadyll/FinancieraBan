<?php

namespace App\Http\Controllers;

use App\Services\MyBankApi;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    public function index(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors([
                'login' => 'Sesión inválida. Inicia sesión de nuevo.'
            ]);
        }

        $error = null;

        // Clientes
        $clientsResp = $api->clients($accessToken, 0, 500);
        $clients = [];

        if (!$clientsResp['ok']) {
            $error = "CLIENTS FALLÓ ({$clientsResp['status']}): " . json_encode($clientsResp['data']);
        } else {
            $clients = is_array($clientsResp['data'])
                ? $clientsResp['data']
                : ($clientsResp['data']['data'] ?? []);
        }

        // Cobradores activos
        $usersResp = $api->users($accessToken, 0, 500);
        $collectors = [];

        if (!$usersResp['ok']) {
            $error = trim(($error ? $error . "\n" : '') . "USERS FALLÓ ({$usersResp['status']}): " . json_encode($usersResp['data']));
        } else {
            $collectors = collect($usersResp['data'])
                ->filter(fn ($u) => ($u['role'] ?? null) === 'USER' && ($u['is_active'] ?? false) === true)
                ->values()
                ->all();
        }

        $maritalOptions = ['SOLTERO', 'CASADO', 'UNION LIBRE', 'VIUDO', 'DIVORCIADO'];

        return view('auth.clients.index', [
            'clients' => $clients,
            'collectors' => $collectors,
            'error' => $error,
            'maritalOptions' => $maritalOptions,
        ]);
    }

    public function store(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors([
                'login' => 'Sesión inválida.'
            ]);
        }

        $maritalOptions = ['SOLTERO', 'CASADO', 'UNION LIBRE', 'VIUDO', 'DIVORCIADO'];

        $data = $request->validate([
            'client_number' => ['required', 'string', 'min:1', 'max:30'],
            'full_name' => ['required', 'string', 'min:3', 'max:150'],
            'address' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => ['required', 'digits:10'],

            'marital_status' => ['required', 'in:' . implode(',', $maritalOptions)],
            'spouse_full_name' => ['nullable', 'string', 'max:150'],

            'guarantor_full_name' => ['required', 'string', 'min:3', 'max:150'],
            'guarantor_address' => ['required', 'string', 'min:3', 'max:255'],
            'guarantor_phone' => ['required', 'digits:10'],
            'guarantor_marital_status' => ['required', 'in:' . implode(',', $maritalOptions)],
        ]);

        $resp = $api->createClient($accessToken, $data);

        if (!$resp['ok']) {
            return back()->withInput()->withErrors([
                'client_create' => "CREATE CLIENT FALLÓ ({$resp['status']}): " . json_encode($resp['data']),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente creado correctamente.');
    }

    public function update(int $clientId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors([
                'login' => 'Sesión inválida.'
            ]);
        }

        $maritalOptions = ['SOLTERO', 'CASADO', 'UNION LIBRE', 'VIUDO', 'DIVORCIADO'];

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:150'],
            'address' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => ['required', 'digits:10'],

            'marital_status' => ['required', 'in:' . implode(',', $maritalOptions)],
            'spouse_full_name' => ['nullable', 'string', 'max:150'],

            'guarantor_full_name' => ['required', 'string', 'min:3', 'max:150'],
            'guarantor_address' => ['required', 'string', 'min:3', 'max:255'],
            'guarantor_phone' => ['required', 'digits:10'],
            'guarantor_marital_status' => ['required', 'in:' . implode(',', $maritalOptions)],
        ]);

        $resp = $api->updateClient($accessToken, $clientId, $data);

        if (!$resp['ok']) {
            return back()->withErrors([
                'client_update' => "UPDATE CLIENT FALLÓ ({$resp['status']}): " . json_encode($resp['data']),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado.');
    }

    public function assign(int $clientId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors([
                'login' => 'Sesión inválida.'
            ]);
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        $resp = $api->assignClient($accessToken, $clientId, (int) $data['user_id']);

        if (!$resp['ok']) {
            return back()->withErrors([
                'client_assign' => "ASSIGN FALLÓ ({$resp['status']}): " . json_encode($resp['data']),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente asignado al cobrador.');
    }
}