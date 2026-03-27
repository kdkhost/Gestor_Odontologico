<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BusinessIntelligenceExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_route_returns_csv_for_authorized_user(): void
    {
        $this->markApplicationAsInstalled();

        Permission::findOrCreate('financeiro.export', 'web');

        $user = User::query()->create([
            'name' => 'Financeiro',
            'email' => 'financeiro@example.com',
            'user_type' => 'staff',
            'is_active' => true,
            'password' => 'senha12345',
        ]);

        $user->givePermissionTo('financeiro.export');

        $response = $this->actingAs($user)->get(route('admin.bi.export', [
            'section' => 'summary',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $summaryContent = $response->streamedContent();

        $this->assertStringContainsString('receita_recebida', $summaryContent);
        $this->assertStringContainsString('repasse_conciliado', $summaryContent);

        $targetsResponse = $this->actingAs($user)->get(route('admin.bi.export', [
            'section' => 'targets',
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $targetsResponse->assertOk();
        $this->assertStringContainsString('tipo_escopo', $targetsResponse->streamedContent());
    }
}
