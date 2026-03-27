<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_reachable_in_testing_context_without_install_lock(): void
    {
        $this->clearInstallationState();

        $this->get('/')
            ->assertOk()
            ->assertSee('Solicitar agendamento');
    }

    public function test_installer_page_is_available_when_system_is_not_installed(): void
    {
        $this->clearInstallationState();

        $this->get(route('install.index'))
            ->assertOk()
            ->assertSee('Instalador');
    }
}
