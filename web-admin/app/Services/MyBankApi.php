<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class MyBankApi
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?: config('mybank_api.base_url');
    }

    // =========================
    // AUTH
    // =========================

    public function login(string $username, string $password): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->post($this->baseUrl . '/auth/login', [
                'username' => $username,
                'password' => $password,
            ]);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function refresh(string $refreshToken): array
    {
        // Si tu API usa header Authorization: Bearer <refresh>
        // cámbialo aquí si lo estás manejando distinto.
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($refreshToken)
            ->post($this->baseUrl . '/auth/refresh');

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function me(string $accessToken): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->get($this->baseUrl . '/auth/me');

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function adminCheck(string $accessToken): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->get($this->baseUrl . '/auth/admin-check');

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    /**
     * ✅ Devuelve Carbon con el "exp" del JWT o null si no se puede leer.
     */
    public function jwtExp(string $jwt): ?int
{
    try {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) return null;

        $payload = $parts[1];

        // base64url decode
        $payload = strtr($payload, '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);

        $json = base64_decode($payload);
        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['exp'])) return null;

        return (int) $data['exp'];
    } catch (\Throwable $e) {
        return null;
    }
}

    // =========================
    // REPORTS
    // =========================

    public function dailyReport(string $accessToken, string $date): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->get($this->baseUrl . '/reports/daily', [
                'date' => $date, // ✅ tu endpoint acepta date
            ]);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function dailyByUser(string $accessToken, string $date): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->get($this->baseUrl . '/reports/daily/by-user', [
                'date' => $date,
            ]);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    // =========================
    // USERS
    // =========================

    public function users(string $accessToken, int $skip = 0, int $limit = 200): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->get($this->baseUrl . '/users', [
                'skip' => $skip,
                'limit' => $limit,
            ]);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function createUser(string $accessToken, array $payload): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->post($this->baseUrl . '/users', $payload);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function toggleUserActive(string $accessToken, int $userId): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->patch($this->baseUrl . "/users/{$userId}/toggle-active");

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function deleteUser(string $accessToken, int $userId): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->delete($this->baseUrl . "/users/{$userId}");

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    // =========================
    // CLIENTS
    // =========================

    public function clients(string $accessToken, int $skip = 0, int $limit = 200): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->get($this->baseUrl . '/clients', [
                'skip' => $skip,
                'limit' => $limit,
            ]);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function createClient(string $accessToken, array $payload): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->post($this->baseUrl . '/clients', $payload);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function updateClient(string $accessToken, int $clientId, array $payload): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->patch($this->baseUrl . "/clients/{$clientId}", $payload);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function assignClient(string $accessToken, int $clientId, int $userId): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->post($this->baseUrl . "/clients/{$clientId}/assign/{$userId}");

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }
}






