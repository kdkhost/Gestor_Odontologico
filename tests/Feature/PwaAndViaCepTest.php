<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PwaAndViaCepTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_returns_branding_from_settings(): void
    {
        $this->markApplicationAsInstalled();

        SystemSetting::query()->create([
            'group' => 'branding',
            'key' => 'app_name',
            'type' => 'string',
            'value' => 'Clínica Sorriso Prime',
            'is_public' => true,
        ]);

        $this->getJson(route('pwa.manifest'))
            ->assertOk()
            ->assertJsonPath('name', 'Clínica Sorriso Prime')
            ->assertJsonPath('display', 'standalone');
    }

    public function test_viacep_endpoint_returns_remote_payload(): void
    {
        $this->markApplicationAsInstalled();

        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ]),
        ]);

        $this->getJson(route('viacep.show', '01001-000'))
            ->assertOk()
            ->assertJsonPath('logradouro', 'Praça da Sé')
            ->assertJsonPath('uf', 'SP');
    }
}
