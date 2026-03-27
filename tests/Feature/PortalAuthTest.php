<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_registration_persists_whatsapp_opt_in(): void
    {
        $this->markApplicationAsInstalled();

        $response = $this->post(route('portal.register.store'), [
            'name' => 'Ana Souza',
            'birth_date' => '1992-08-12',
            'email' => 'ana@example.com',
            'phone' => '(11) 98888-1111',
            'whatsapp' => '(11) 98888-1111',
            'whatsapp_opt_in' => true,
            'document' => '123.456.789-00',
            'password' => 'SenhaForte123',
            'password_confirmation' => 'SenhaForte123',
        ]);

        $response->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'ana@example.com',
            'phone' => '11988881111',
            'document' => '12345678900',
            'user_type' => 'patient',
        ]);

        $this->assertDatabaseHas('patients', [
            'name' => 'Ana Souza',
            'phone' => '11988881111',
            'whatsapp' => '11988881111',
            'cpf' => '12345678900',
            'whatsapp_opt_in' => true,
        ]);
    }
}
