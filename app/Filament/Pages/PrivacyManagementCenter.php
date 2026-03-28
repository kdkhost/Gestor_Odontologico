<?php

namespace App\Filament\Pages;

use App\Models\Patient;
use App\Models\PrivacyRequest;
use App\Models\Unit;
use App\Services\PrivacyManagementService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class PrivacyManagementCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Central LGPD';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuracoes';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Central LGPD';

    protected string $view = 'filament.pages.privacy-management-center';

    public array $filters = [];

    public array $unitOptions = [];

    public array $requestTypeOptions = [];

    public array $summary = [];

    public array $recentRequests = [];

    public array $candidatePatients = [];

    public function mount(PrivacyManagementService $privacy): void
    {
        $this->unitOptions = Unit::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $this->requestTypeOptions = $privacy->requestTypeOptions();

        $this->filters = [
            'unit_id' => auth()->user()?->hasRole('superadmin') ? null : auth()->user()?->unit_id,
            'status' => null,
            'type' => null,
        ];

        $this->loadData($privacy);
    }

    public function refreshData(PrivacyManagementService $privacy): void
    {
        $this->validateFilters();
        $this->loadData($privacy);

        Notification::make()
            ->title('Central LGPD atualizada.')
            ->success()
            ->send();
    }

    public function createRequest(PrivacyManagementService $privacy, int $patientId, string $type): void
    {
        try {
            $privacy->createRequest(
                patient: Patient::query()->findOrFail($patientId),
                type: $type,
                payload: ['requested_by_user_id' => auth()->id()],
            );

            $this->loadData($privacy);

            Notification::make()
                ->title('Solicitacao LGPD registrada com sucesso.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function processRequest(PrivacyManagementService $privacy, int $requestId): void
    {
        try {
            $privacy->processRequest(
                request: PrivacyRequest::query()->findOrFail($requestId),
                payload: ['processed_by_user_id' => auth()->id()],
            );

            $this->loadData($privacy);

            Notification::make()
                ->title('Solicitacao LGPD processada com sucesso.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('configuracoes.manage') === true;
    }

    private function loadData(PrivacyManagementService $privacy): void
    {
        $unitId = filled($this->filters['unit_id'] ?? null) ? (int) $this->filters['unit_id'] : null;

        $this->summary = $privacy->summary($unitId);
        $this->recentRequests = $privacy->recentRequests(
            unitId: $unitId,
            status: $this->filters['status'] ?: null,
            type: $this->filters['type'] ?: null,
        )->map(fn (PrivacyRequest $request) => $request->toArray())->all();
        $this->candidatePatients = $privacy->candidatePatients($unitId)->values()->all();
    }

    private function validateFilters(): void
    {
        $this->filters = Validator::make($this->filters, [
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'status' => ['nullable', 'string', 'in:pending,processing,completed,failed,cancelled'],
            'type' => ['nullable', 'string', 'in:export,anonymize'],
        ])->validate();
    }
}
