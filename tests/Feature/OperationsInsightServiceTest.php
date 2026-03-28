<?php

namespace Tests\Feature;

use App\Models\AccountReceivable;
use App\Models\Appointment;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Unit;
use App\Services\OperationsInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperationsInsightServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_scoped_operational_snapshot(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 27, 9, 0, 0, config('app.timezone')));

        $unitA = Unit::query()->create([
            'name' => 'Matriz Centro',
            'slug' => 'matriz-centro',
            'is_active' => true,
        ]);

        $unitB = Unit::query()->create([
            'name' => 'Filial Norte',
            'slug' => 'filial-norte',
            'is_active' => true,
        ]);

        $patientConfirmed = Patient::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Ana Souza',
            'is_active' => true,
        ]);

        $patientRequested = Patient::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Bruno Lima',
            'is_active' => true,
        ]);

        $patientNoShow = Patient::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Carla Nunes',
            'is_active' => true,
        ]);

        $patientDormant = Patient::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Daniel Rocha',
            'last_visit_at' => now()->subDays(120),
            'is_active' => true,
        ]);

        $patientOtherUnit = Patient::query()->create([
            'unit_id' => $unitB->id,
            'name' => 'Elisa Prado',
            'is_active' => true,
        ]);

        Appointment::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientConfirmed->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'scheduled_start' => now()->setHour(9)->setMinute(0),
            'scheduled_end' => now()->setHour(10)->setMinute(0),
        ]);

        Appointment::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientRequested->id,
            'status' => 'requested',
            'origin' => 'portal',
            'scheduled_start' => now()->addHours(3),
            'scheduled_end' => now()->addHours(4),
        ]);

        Appointment::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientNoShow->id,
            'status' => 'no_show',
            'origin' => 'phone',
            'scheduled_start' => now()->subDays(10),
            'scheduled_end' => now()->subDays(10)->addHour(),
        ]);

        Appointment::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientNoShow->id,
            'status' => 'no_show',
            'origin' => 'phone',
            'scheduled_start' => now()->subDays(40),
            'scheduled_end' => now()->subDays(40)->addHour(),
        ]);

        Appointment::query()->create([
            'unit_id' => $unitB->id,
            'patient_id' => $patientOtherUnit->id,
            'status' => 'requested',
            'origin' => 'portal',
            'scheduled_start' => now()->addHours(2),
            'scheduled_end' => now()->addHours(3),
        ]);

        AccountReceivable::query()->create([
            'unit_id' => $unitA->id,
            'patient_id' => $patientConfirmed->id,
            'description' => 'Plano atrasado',
            'status' => 'open',
            'total_amount' => 500,
            'net_amount' => 500,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        AccountReceivable::query()->create([
            'unit_id' => $unitB->id,
            'patient_id' => $patientOtherUnit->id,
            'description' => 'Outro título',
            'status' => 'open',
            'total_amount' => 900,
            'net_amount' => 900,
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $criticalItem = InventoryItem::query()->create([
            'unit_id' => $unitA->id,
            'name' => 'Resina A2',
            'unit_measure' => 'un',
            'minimum_stock' => 5,
            'current_stock' => 2,
            'cost_price' => 10,
            'sale_price' => 20,
            'is_active' => true,
        ]);

        InventoryItem::query()->create([
            'unit_id' => $unitB->id,
            'name' => 'Luva nitrílica',
            'unit_measure' => 'cx',
            'minimum_stock' => 1,
            'current_stock' => 20,
            'cost_price' => 15,
            'sale_price' => 30,
            'is_active' => true,
        ]);

        InventoryBatch::query()->create([
            'inventory_item_id' => $criticalItem->id,
            'unit_id' => $unitA->id,
            'batch_code' => 'LOT-001',
            'quantity_received' => 10,
            'quantity_available' => 4,
            'purchase_price' => 10,
            'expires_at' => now()->addDays(12)->toDateString(),
            'received_at' => now()->subDay(),
        ]);

        $snapshot = app(OperationsInsightService::class)->snapshot($unitA->id, days: 7, limit: 5);

        $this->assertSame('Matriz Centro', $snapshot['scope']['label']);
        $this->assertSame(2, $snapshot['stats']['today_appointments']);
        $this->assertSame(1, $snapshot['stats']['today_pending_confirmation']);
        $this->assertSame(500.0, $snapshot['stats']['overdue_total']);
        $this->assertSame(1, $snapshot['stats']['critical_stock_count']);
        $this->assertSame(1, $snapshot['stats']['expiring_batch_count']);
        $this->assertEquals(50.0, $snapshot['stats']['confirmation_rate']);
        $this->assertEquals(33.3, $snapshot['stats']['no_show_rate']);
        $this->assertCount(7, $snapshot['trends']['labels']);
        $this->assertCount(1, $snapshot['alerts']['appointments_needing_confirmation']);
        $this->assertSame('Bruno Lima', $snapshot['alerts']['appointments_needing_confirmation']->first()?->patient?->name);
        $this->assertCount(1, $snapshot['alerts']['overdue_receivables']);
        $this->assertSame('Ana Souza', $snapshot['alerts']['overdue_receivables']->first()?->patient?->name);
        $this->assertCount(1, $snapshot['alerts']['low_stock_items']);
        $this->assertSame('Resina A2', $snapshot['alerts']['low_stock_items']->first()?->name);
        $this->assertCount(1, $snapshot['alerts']['expiring_batches']);
        $this->assertCount(1, $snapshot['alerts']['repeat_no_show_patients']);
        $this->assertSame('Carla Nunes', $snapshot['alerts']['repeat_no_show_patients']->first()?->patient?->name);
        $this->assertCount(1, $snapshot['alerts']['reactivation_candidates']);
        $this->assertSame('Daniel Rocha', $snapshot['alerts']['reactivation_candidates']->first()?->name);

        Carbon::setTestNow();
    }
}
