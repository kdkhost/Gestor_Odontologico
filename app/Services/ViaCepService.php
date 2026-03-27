<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ViaCepService
{
    public function lookup(string $cep): ?array
    {
        $cep = preg_replace('/\D+/', '', $cep ?? '');

        if (strlen((string) $cep) !== 8) {
            return null;
        }

        $response = Http::baseUrl(config('services.viacep.base_url'))
            ->timeout(5)
            ->acceptJson()
            ->get("{$cep}/json/");

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (data_get($payload, 'erro') === true) {
            return null;
        }

        return $payload;
    }
}
