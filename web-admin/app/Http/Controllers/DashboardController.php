<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\MyBankApi;

class DashboardController extends Controller
{
    public function index(Request $request, MyBankApi $api)
    {
        // Fecha seleccionada (?date=YYYY-MM-DD) o hoy
        $date = $request->query('date', Carbon::now()->format('Y-m-d'));

        // Token guardado en sesión (lo guardas al hacer login)
        $accessToken = session('mybank_access_token');

        // Si no hay token, fuera al login
        if (!$accessToken) {
            return redirect()->route('login')->withErrors([
                'login' => 'Sesión inválida. Inicia sesión de nuevo.',
            ]);
        }

        // Variables para la vista
        $me = null;
        $daily = null;
        $items = [];
        $activeCollectors = null;

        // Errores detallados (para que sepas EXACTO qué falló)
        $errors = [];

        // 1) /auth/me
        $meResponse = $api->me($accessToken);
        if (!$meResponse['ok']) {
            $errors[] = "ME FALLÓ ({$meResponse['status']}): " . json_encode($meResponse['data']);
        } else {
            $me = $meResponse['data'];
        }

        // 2) /reports/daily?date=
        $dailyResponse = $api->dailyReport($accessToken, $date);
        if (!$dailyResponse['ok']) {
            $errors[] = "DAILY FALLÓ ({$dailyResponse['status']}): " . json_encode($dailyResponse['data']);
        } else {
            $daily = $dailyResponse['data'];
        }

        // 3) /reports/daily/by-user?date=
        $byUserResponse = $api->dailyByUser($accessToken, $date);
        if (!$byUserResponse['ok']) {
            $errors[] = "BY-USER FALLÓ ({$byUserResponse['status']}): " . json_encode($byUserResponse['data']);
        } else {
            // Tu API devuelve: { date: ..., items: [...] }
            $items = $byUserResponse['data']['items'] ?? [];
        }

        // 4) /users para contar cobradores activos
        $usersResponse = $api->users($accessToken, 0, 200);
        if (!$usersResponse['ok']) {
            $errors[] = "USERS FALLÓ ({$usersResponse['status']}): " . json_encode($usersResponse['data']);
        } else {
            $activeCollectors = collect($usersResponse['data'])
                ->filter(fn ($u) => ($u['role'] ?? null) === 'USER' && ($u['is_active'] ?? false) === true)
                ->count();
        }

        // Si hay errores, los juntamos en un string (para mostrar en la alerta)
        $error = count($errors) ? implode("\n", $errors) : null;

        return view('auth.dashboard.index', [
            'me' => $me,
            'date' => $date,
            'daily' => $daily,
            'items' => $items,
            'activeCollectors' => $activeCollectors,
            'error' => $error,
        ]);
    }
}



