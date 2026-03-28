<?php

namespace Tests\Feature;

use App\Services\InstallerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_installer_when_system_is_not_installed(): void
    {
        $this->clearInstallationState();
        $this->assertFalse(app(InstallerService::class)->isInstalled());

        $this->get('/')
            ->assertRedirect(route('install.index'));
    }

    public function test_home_page_is_reachable_when_system_is_installed(): void
    {
        $this->markApplicationAsInstalled();
        $this->assertTrue(app(InstallerService::class)->isInstalled());

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

    public function test_installer_redirects_home_when_system_is_already_installed(): void
    {
        $this->markApplicationAsInstalled();

        $this->get(route('install.index'))
            ->assertRedirect(route('home'));
    }
}
