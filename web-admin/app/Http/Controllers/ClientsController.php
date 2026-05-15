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
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida. Inicia sesión de nuevo.']);
        }

        $error = null;

        $clientsResp = $api->clients($accessToken, 0, 500);
        $clients = [];

        if (!$clientsResp['ok']) {
            $error = "CLIENTS FALLÓ ({$clientsResp['status']}): " . json_encode($clientsResp['data']);
        } else {
            $clients = is_array($clientsResp['data']) ? $clientsResp['data'] : ($clientsResp['data']['data'] ?? []);
        }

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
            'clients'        => $clients,
            'collectors'     => $collectors,
            'error'          => $error,
            'maritalOptions' => $maritalOptions,
        ]);
    }

    public function store(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $maritalOptions = ['SOLTERO', 'CASADO', 'UNION LIBRE', 'VIUDO', 'DIVORCIADO'];

        $data = $request->validate([
            'full_name'        => ['required', 'string', 'min:3', 'max:150'],
            'phone'            => ['required', 'digits:10'],
            'address'          => ['required', 'string', 'min:3', 'max:255'],
            'marital_status'   => ['required', 'in:' . implode(',', $maritalOptions)],
            'spouse_full_name' => ['nullable', 'string', 'max:150'],

            // ✅ Nuevos campos opcionales
            'birth_date'       => ['nullable', 'date', 'before:today'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'monthly_income'   => ['nullable', 'numeric', 'min:0'],

            'guarantor_full_name'      => ['required', 'string', 'min:3', 'max:150'],
            'guarantor_address'        => ['required', 'string', 'min:3', 'max:255'],
            'guarantor_phone'          => ['required', 'digits:10'],
            'guarantor_marital_status' => ['required', 'in:' . implode(',', $maritalOptions)],
        ], [
            'full_name.required'    => 'El nombre completo del cliente es obligatorio.',
            'full_name.min'         => 'El nombre completo del cliente debe tener al menos 3 caracteres.',
            'phone.required'        => 'El teléfono del cliente es obligatorio.',
            'phone.digits'          => 'El teléfono del cliente debe tener exactamente 10 dígitos.',
            'address.required'      => 'La dirección del cliente es obligatoria.',
            'address.min'           => 'La dirección del cliente debe tener al menos 3 caracteres.',
            'marital_status.required' => 'El estado civil del cliente es obligatorio.',
            'marital_status.in'       => 'El estado civil del cliente no es válido.',
            'guarantor_full_name.required' => 'El nombre completo del aval es obligatorio.',
            'guarantor_full_name.min'      => 'El nombre completo del aval debe tener al menos 3 caracteres.',
            'guarantor_address.required'   => 'La dirección del aval es obligatoria.',
            'guarantor_address.min'        => 'La dirección del aval debe tener al menos 3 caracteres.',
            'guarantor_phone.required'     => 'El teléfono del aval es obligatorio.',
            'guarantor_phone.digits'       => 'El teléfono del aval debe tener exactamente 10 dígitos.',
            'guarantor_marital_status.required' => 'El estado civil del aval es obligatorio.',
            'guarantor_marital_status.in'       => 'El estado civil del aval no es válido.',
        ]);

        $resp = $api->createClient($accessToken, $data);

        if (!$resp['ok']) {
            $detail = $resp['data']['detail'] ?? json_encode($resp['data']);
            return back()->withInput()->withErrors(['client_create' => $detail]);
        }

        $newClientId = $resp['data']['id'] ?? null;
        return redirect()
            ->route('clients.index', ['created' => 1, 'new_id' => $newClientId])
            ->with('success', "✅ Cliente '{$resp['data']['full_name']}' registrado. Puedes modificarlo o eliminarlo.");
    }

    public function destroy(int $clientId, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $resp = $api->deleteClient($accessToken, $clientId);

        if (!$resp['ok']) {
            $detail = $resp['data']['detail'] ?? json_encode($resp['data']);
            return back()->withErrors(['client_delete' => $detail]);
        }

        return redirect()->route('clients.index')->with('success', '🗑️ Cliente eliminado correctamente.');
    }

    public function update(int $clientId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $maritalOptions = ['SOLTERO', 'CASADO', 'UNION LIBRE', 'VIUDO', 'DIVORCIADO'];

        $data = $request->validate([
            'full_name'        => ['required', 'string', 'min:3', 'max:150'],
            'phone'            => ['required', 'digits:10'],
            'address'          => ['required', 'string', 'min:3', 'max:255'],
            'marital_status'   => ['required', 'in:' . implode(',', $maritalOptions)],
            'spouse_full_name' => ['nullable', 'string', 'max:150'],

            // ✅ Nuevos campos opcionales
            'birth_date'       => ['nullable', 'date', 'before:today'],
            'occupation'       => ['nullable', 'string', 'max:100'],
            'monthly_income'   => ['nullable', 'numeric', 'min:0'],

            'guarantor_full_name'      => ['required', 'string', 'min:3', 'max:150'],
            'guarantor_address'        => ['required', 'string', 'min:3', 'max:255'],
            'guarantor_phone'          => ['required', 'digits:10'],
            'guarantor_marital_status' => ['required', 'in:' . implode(',', $maritalOptions)],
        ], [
            'full_name.required'    => 'El nombre completo del cliente es obligatorio.',
            'full_name.min'         => 'El nombre completo del cliente debe tener al menos 3 caracteres.',
            'phone.required'        => 'El teléfono del cliente es obligatorio.',
            'phone.digits'          => 'El teléfono del cliente debe tener exactamente 10 dígitos.',
            'address.required'      => 'La dirección del cliente es obligatoria.',
            'address.min'           => 'La dirección del cliente debe tener al menos 3 caracteres.',
            'marital_status.required' => 'El estado civil del cliente es obligatorio.',
            'marital_status.in'       => 'El estado civil del cliente no es válido.',
            'guarantor_full_name.required' => 'El nombre completo del aval es obligatorio.',
            'guarantor_full_name.min'      => 'El nombre completo del aval debe tener al menos 3 caracteres.',
            'guarantor_address.required'   => 'La dirección del aval es obligatoria.',
            'guarantor_address.min'        => 'La dirección del aval debe tener al menos 3 caracteres.',
            'guarantor_phone.required'     => 'El teléfono del aval es obligatorio.',
            'guarantor_phone.digits'       => 'El teléfono del aval debe tener exactamente 10 dígitos.',
            'guarantor_marital_status.required' => 'El estado civil del aval es obligatorio.',
            'guarantor_marital_status.in'       => 'El estado civil del aval no es válido.',
        ]);

        $resp = $api->updateClient($accessToken, $clientId, $data);

        if (!$resp['ok']) {
            return back()->withInput()->withErrors([
                'client_update' => "UPDATE CLIENT FALLÓ ({$resp['status']}): " . json_encode($resp['data']),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function assign(int $clientId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ], [
            'user_id.required' => 'Debes seleccionar un cobrador.',
            'user_id.integer'  => 'El cobrador seleccionado no es válido.',
            'user_id.min'      => 'El cobrador seleccionado no es válido.',
        ]);

        $resp = $api->assignClient($accessToken, $clientId, (int) $data['user_id']);

        if (!$resp['ok']) {
            return back()->withErrors([
                'client_assign' => "ASSIGN FALLÓ ({$resp['status']}): " . json_encode($resp['data']),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente asignado al cobrador correctamente.');
    }
}