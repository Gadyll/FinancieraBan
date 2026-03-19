<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MyBankApi
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?: (string) config('mybank_api.base_url', ''), '/');

        if ($this->baseUrl === '') {
            $this->baseUrl = 'http://127.0.0.1:8000/api/v1';
        }
    }

    // =========================
    // HELPERS
    // =========================

    private function ok($response): array
    {
        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }
        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function jwtExp(string $jwt): ?int
    {
        try {
            $parts = explode('.', $jwt);
            if (count($parts) < 2) return null;

            $payload = $parts[1];
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

        return $this->ok($response);
    }

    public function refresh(string $refreshToken): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($refreshToken)
            ->post($this->baseUrl . '/auth/refresh');

        return $this->ok($response);
    }

    public function me(string $accessToken): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . '/auth/me');

        return $this->ok($response);
    }

    public function adminCheck(string $accessToken): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . '/auth/admin-check');

        return $this->ok($response);
    }

    // =========================
    // REPORTS
    // =========================

    public function dailyReport(string $accessToken, string $date): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . '/reports/daily', [
                'date' => $date,
            ]);

        return $this->ok($response);
    }

    public function dailyByUser(string $accessToken, string $date): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . '/reports/daily/by-user', [
                'date' => $date,
            ]);

        return $this->ok($response);
    }

    // =========================
    // USERS
    // =========================

    public function users(string $accessToken, int $skip = 0, int $limit = 200): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . '/users', [
                'skip' => $skip,
                'limit' => $limit,
            ]);

        return $this->ok($response);
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

    public function resetUserPassword(string $accessToken, int $userId): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->post($this->baseUrl . "/users/{$userId}/reset-password");

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
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . '/clients', [
                'skip' => $skip,
                'limit' => $limit,
            ]);

        return $this->ok($response);
    }

    public function createClient(string $accessToken, array $payload): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->post($this->baseUrl . '/clients', $payload);

        return $this->ok($response);
    }

    public function updateClient(string $accessToken, int $clientId, array $payload): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->patch($this->baseUrl . "/clients/{$clientId}", $payload);

        return $this->ok($response);
    }

    /**
     * ✅ COMPAT:
     * - Backend viejo: POST /clients/{client_id}/assign/{user_id}
     * - Backend nuevo: POST /clients/{client_id}/assign  body {user_id}
     */
    public function assignClient(string $accessToken, int $clientId, int $userId): array
    {
        // 1) Intento versión "nueva" (body)
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->post($this->baseUrl . "/clients/{$clientId}/assign", [
                'user_id' => $userId,
            ]);

        if (!$response->failed()) {
            return $this->ok($response);
        }

        // 2) Fallback versión "vieja" (path param)
        if (in_array($response->status(), [404, 405])) {
            $fallback = Http::acceptJson()
                ->timeout(15)
                ->withToken($accessToken)
                ->post($this->baseUrl . "/clients/{$clientId}/assign/{$userId}");

            return $this->ok($fallback);
        }

        return $this->ok($response);
    }

    // ====== PREPARADOS PARA SIGUIENTE PASO (HISTORIAL) ======

    public function clientLoans(string $accessToken, int $clientId): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . "/clients/{$clientId}/loans");

        return $this->ok($response);
    }

    public function clientDashboard(string $accessToken, int $clientId): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . "/clients/{$clientId}/dashboard");

        return $this->ok($response);
    }

    public function loanSummary(string $accessToken, int $loanId): array
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->withToken($accessToken)
            ->get($this->baseUrl . "/loans/{$loanId}/summary");

        return $this->ok($response);
    }
}







