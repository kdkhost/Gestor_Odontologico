<?php

namespace App\Filament\Pages;

use App\Models\InsuranceAuthorization;
use App\Models\TreatmentPlan;
use App\Models\Unit;
use App\Services\InsuranceAuthorizationService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class InsuranceAuthorizationCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Autorizacoes de convenio';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestao';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Central de autorizacoes de convenio';

    protected string $view = 'filament.pages.insurance-authorization-center';

    public array $filters = [];

    public array $summary = [];

    public array $candidatePlans = [];

    public array $recentAuthorizations = [];

    public array $unitOptions = [];

    public array $statusOptions = [];

    public function mount(InsuranceAuthorizationService $service): void
    {
        $this->unitOptions = Unit::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $this->statusOptions = $service->statusOptions();

        $this->filters = [
            'unit_id' => auth()->user()?->hasRole('superadmin') ? null : auth()->user()?->unit_id,
            'status' => null,
        ];

        $this->loadData($service);
    }

    public function refreshData(InsuranceAuthorizationService $service): void
    {
        $this->validateFilters();
        $this->loadData($service);

        Notification::make()
            ->title('Central de convenio atualizada.')
            ->success()
            ->send();
    }

    public function createAuthorization(InsuranceAuthorizationService $service, int $treatmentPlanId): void
    {
        try {
            $service->createDraft(
                plan: TreatmentPlan::query()->findOrFail($treatmentPlanId),
                payload: ['created_by_user_id' => auth()->id()],
            );

            $this->loadData($service);

            Notification::make()
                ->title('Guia de convenio gerada em rascunho.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function submitAuthorization(InsuranceAuthorizationService $service, int $authorizationId): void
    {
        try {
            $service->submit(
                authorization: InsuranceAuthorization::query()->findOrFail($authorizationId),
                payload: ['message' => 'Guia enviada para acompanhamento da operadora.'],
            );

            $this->loadData($service);

            Notification::make()
                ->title('Guia enviada para a operadora.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function authorizeAuthorization(InsuranceAuthorizationService $service, int $authorizationId): void
    {
        try {
            $authorization = InsuranceAuthorization::query()
                ->with('items')
                ->findOrFail($authorizationId);

            $service->registerResponse(
                authorization: $authorization,
                itemPayloads: $authorization->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'status' => 'authorized',
                    'authorized_quantity' => $item->requested_quantity,
                    'authorized_amount' => $item->requested_amount,
                ])->all(),
                payload: ['message' => 'Guia liberada integralmente pela operadora.'],
            );

            $this->loadData($service);

            Notification::make()
                ->title('Guia autorizada com sucesso.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function denyAuthorization(InsuranceAuthorizationService $service, int $authorizationId): void
    {
        try {
            $authorization = InsuranceAuthorization::query()
                ->with('items')
                ->findOrFail($authorizationId);

            $service->registerResponse(
                authorization: $authorization,
                itemPayloads: $authorization->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'status' => 'denied',
                    'denial_reason' => 'Negado no retorno operacional da operadora.',
                ])->all(),
                payload: ['message' => 'Guia negada pela operadora.'],
            );

            $this->loadData($service);

            Notification::make()
                ->title('Guia marcada como negada.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function expireAuthorizations(InsuranceAuthorizationService $service): void
    {
        $count = $service->markExpired(
            ($this->filters['unit_id'] ?? null) ? (int) $this->filters['unit_id'] : null,
        );

        $this->loadData($service);

        Notification::make()
            ->title($count > 0 ? "Guias expiradas: {$count}" : 'Nenhuma guia vencida para expirar.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('planos.update') === true;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = InsuranceAuthorization::query()
            ->where('status', 'submitted')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== null ? 'warning' : null;
    }

    private function loadData(InsuranceAuthorizationService $service): void
    {
        $unitId = ($this->filters['unit_id'] ?? null) ? (int) $this->filters['unit_id'] : null;

        $this->summary = $service->summary($unitId);
        $this->candidatePlans = $service->candidateTreatmentPlans($unitId)->all();
        $this->recentAuthorizations = $service->recentAuthorizations(
            unitId: $unitId,
            status: $this->filters['status'] ?: null,
        )->map(fn (InsuranceAuthorization $authorization) => $authorization->toArray())->all();
    }

    private function validateFilters(): void
    {
        $this->filters = Validator::make($this->filters, [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'status' => ['nullable', 'string', 'in:draft,submitted,authorized,partially_authorized,denied,expired,cancelled'],
        ])->validate();
    }
}
