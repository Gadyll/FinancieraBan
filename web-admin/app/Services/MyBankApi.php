<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MyBankApi
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.mybank_api.base_url'), '/');
    }

    public function login(string $username, string $password): array
    {
        $response = Http::acceptJson()
            ->timeout(10)
            ->post($this->baseUrl . '/auth/login', [
                'username' => $username,
                'password' => $password,
            ]);

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }

    public function me(string $accessToken): array
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(10)
            ->get($this->baseUrl . '/auth/me');

        if ($response->failed()) {
            return ['ok' => false, 'status' => $response->status(), 'data' => $response->json()];
        }

        return ['ok' => true, 'status' => $response->status(), 'data' => $response->json()];
    }
}
