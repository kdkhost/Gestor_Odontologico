<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_redirects_guest_to_login(): void
    {
        $this->markApplicationAsInstalled();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_user_can_login_with_document_and_access_dashboard(): void
    {
        $this->markApplicationAsInstalled();
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create([
            'name' => 'Gestora Principal',
            'email' => 'gestora@clinica.test',
            'document' => '123.456.789-01',
            'phone' => '(11) 98888-7777',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => Hash::make('SenhaForte123'),
        ]);

        $user->syncRoles(['superadmin']);

        $this->post(route('admin.login.attempt'), [
            'login' => '12345678901',
            'password' => 'SenhaForte123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Navegacao operacional', false);
    }

    public function test_workspace_wraps_embedded_core_module_inside_adminlte_shell(): void
    {
        $this->markApplicationAsInstalled();
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create([
            'user_type' => 'staff',
            'is_active' => true,
        ]);

        $user->syncRoles(['superadmin']);

        $this->actingAs($user)
            ->get(route('admin.workspace', ['slug' => 'agenda-visual']))
            ->assertOk()
            ->assertSee('Workspace administrativo')
            ->assertSee('/admin/core/visual-agenda', false);
    }

    public function test_workspace_redirects_native_module_to_native_route(): void
    {
        $this->markApplicationAsInstalled();
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create([
            'user_type' => 'staff',
            'is_active' => true,
        ]);

        $user->syncRoles(['superadmin']);

        $this->actingAs($user)
            ->get(route('admin.workspace', ['slug' => 'pacientes']))
            ->assertRedirect(route('admin.patients.index'));
    }

    public function test_native_patient_pages_render_inside_adminlte_shell(): void
    {
        $this->markApplicationAsInstalled();
        $this->seed(DatabaseSeeder::class);

        $unit = Unit::query()->create([
            'name' => 'Matriz Centro',
            'slug' => 'matriz-centro',
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Patricia Lima',
            'phone' => '(11) 97777-6666',
            'whatsapp' => '(11) 97777-6666',
            'email' => 'patricia@example.com',
            'last_visit_at' => now()->subDays(20),
            'is_active' => true,
            'whatsapp_opt_in' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'scheduled_start' => now()->addDay()->setHour(10),
            'scheduled_end' => now()->addDay()->setHour(11),
        ]);

        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'user_type' => 'staff',
            'is_active' => true,
        ]);

        $user->syncRoles(['superadmin']);

        $this->actingAs($user)
            ->get(route('admin.patients.index'))
            ->assertOk()
            ->assertSee('Painel de pacientes')
            ->assertSee('Patricia Lima');

        $this->actingAs($user)
            ->get(route('admin.patients.show', ['patient' => $patient->id]))
            ->assertOk()
            ->assertSee('Perfil 360 do paciente')
            ->assertSee('Patricia Lima');
    }

    public function test_native_appointment_page_renders_inside_adminlte_shell(): void
    {
        $this->markApplicationAsInstalled();
        $this->seed(DatabaseSeeder::class);

        $unit = Unit::query()->create([
            'name' => 'Matriz Sul',
            'slug' => 'matriz-sul',
            'is_active' => true,
        ]);

        $patient = Patient::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Marcos Dias',
            'is_active' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unit->id,
            'patient_id' => $patient->id,
            'status' => 'requested',
            'origin' => 'portal',
            'scheduled_start' => now()->setHour(9),
            'scheduled_end' => now()->setHour(10),
        ]);

        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'user_type' => 'staff',
            'is_active' => true,
        ]);

        $user->syncRoles(['superadmin']);

        $this->actingAs($user)
            ->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('Agenda operacional')
            ->assertSee('Marcos Dias');
    }

    public function test_legacy_core_login_redirects_to_new_adminlte_login(): void
    {
        $this->markApplicationAsInstalled();

        $this->get('/admin/core/login')
            ->assertRedirect(route('admin.login'));
    }
}
