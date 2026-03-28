<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PrivacyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PrivacyManagementService
{
    public function requestTypeOptions(): array
    {
        return [
            'export' => 'Exportacao de dados',
            'anonymize' => 'Anonimizacao do cadastro',
        ];
    }

    public function summary(?int $unitId = null): array
    {
        $baseQuery = $this->scopeRequests(PrivacyRequest::query(), $unitId);

        return [
            'pending_count' => (clone $baseQuery)->where('status', 'pending')->count(),
            'overdue_count' => (clone $baseQuery)
                ->whereIn('status', ['pending', 'processing'])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now(config('app.timezone')))
                ->count(),
            'completed_count' => (clone $baseQuery)->where('status', 'completed')->count(),
            'anonymized_patients_count' => $this->scopePatients(Patient::query(), $unitId)
                ->whereNotNull('anonymized_at')
                ->count(),
        ];
    }

    public function recentRequests(?int $unitId = null, ?string $status = null, ?string $type = null, int $limit = 15): Collection
    {
        return $this->scopeRequests(
            PrivacyRequest::query()->with(['unit', 'patient', 'requestedBy', 'processedBy']),
            $unitId,
        )
            ->when(filled($status), fn (Builder $query): Builder => $query->where('status', $status))
            ->when(filled($type), fn (Builder $query): Builder => $query->where('request_type', $type))
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function candidatePatients(?int $unitId = null, int $limit = 12): Collection
    {
        return $this->scopePatients(Patient::query()->with('unit'), $unitId)
            ->whereNull('anonymized_at')
            ->where(function (Builder $query): void {
                $query->whereNotNull('cpf')
                    ->orWhereNotNull('email')
                    ->orWhereNotNull('phone')
                    ->orWhereNotNull('whatsapp')
                    ->orWhereNotNull('user_id');
            })
            ->orderByRaw('CASE WHEN last_visit_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('last_visit_at')
            ->limit($limit)
            ->get()
            ->map(function (Patient $patient): array {
                $dataPoints = collect([
                    $patient->cpf,
                    $patient->email,
                    $patient->phone,
                    $patient->whatsapp,
                    $patient->zip_code,
                    $patient->street,
                    $patient->number,
                ])->filter(fn ($value): bool => filled($value))->count();

                return [
                    'patient_id' => $patient->id,
                    'name' => $patient->name,
                    'unit_name' => $patient->unit?->name ?? 'Sem unidade',
                    'last_visit_at' => $patient->last_visit_at,
                    'has_portal_account' => $patient->user_id !== null,
                    'has_whatsapp_opt_in' => (bool) $patient->whatsapp_opt_in,
                    'data_points_count' => $dataPoints,
                ];
            });
    }

    public function createRequest(Patient $patient, string $type, array $payload = []): PrivacyRequest
    {
        if (! array_key_exists($type, $this->requestTypeOptions())) {
            throw new RuntimeException('Tipo de solicitacao LGPD invalido.');
        }

        $hasActiveRequest = $patient->privacyRequests()
            ->where('request_type', $type)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($hasActiveRequest) {
            throw new RuntimeException('Ja existe uma solicitacao LGPD aberta para este paciente e este tipo.');
        }

        return PrivacyRequest::query()->create([
            'unit_id' => $patient->unit_id,
            'patient_id' => $patient->id,
            'requested_by_user_id' => $payload['requested_by_user_id'] ?? auth()->id(),
            'request_type' => $type,
            'status' => 'pending',
            'requester_name' => $payload['requester_name'] ?? $patient->name,
            'requester_email' => $payload['requester_email'] ?? $patient->email,
            'requester_channel' => $payload['requester_channel'] ?? 'painel_admin',
            'legal_basis' => $payload['legal_basis'] ?? 'solicitacao do titular',
            'requested_at' => now(config('app.timezone')),
            'due_at' => now(config('app.timezone'))->addDays((int) ($payload['due_in_days'] ?? 15)),
            'reason' => $payload['reason'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'payload' => $payload['payload'] ?? null,
        ])->load(['unit', 'patient', 'requestedBy', 'processedBy']);
    }

    public function processRequest(PrivacyRequest $request, array $payload = []): PrivacyRequest
    {
        if ($request->status === 'completed') {
            return $request->refresh()->load(['unit', 'patient', 'requestedBy', 'processedBy']);
        }

        if ($request->status === 'cancelled') {
            throw new RuntimeException('Solicitacoes canceladas nao podem ser processadas.');
        }

        $request->loadMissing([
            'patient.unit',
            'patient.user',
            'patient.guardians',
            'patient.subscriptions',
            'patient.appointments',
            'patient.clinicalRecords',
            'patient.treatmentPlans',
            'patient.accountsReceivable',
            'patient.documentAcceptances.documentTemplate',
            'patient.fiscalInvoices',
            'requestedBy',
            'processedBy',
        ]);

        $request->update([
            'status' => 'processing',
            'processed_by_user_id' => $payload['processed_by_user_id'] ?? auth()->id(),
        ]);

        try {
            if ($request->request_type === 'export') {
                return $this->processExport($request);
            }

            if ($request->request_type === 'anonymize') {
                return $this->processAnonymization($request);
            }

            throw new RuntimeException('Tipo de solicitacao LGPD sem fluxo implementado.');
        } catch (\Throwable $exception) {
            $request->update([
                'status' => 'failed',
                'last_error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function processExport(PrivacyRequest $request): PrivacyRequest
    {
        $snapshot = $this->buildPatientSnapshot($request->patient);
        $path = "lgpd-exports/paciente-{$request->patient_id}/solicitacao-{$request->id}.json";

        Storage::disk('local')->put(
            $path,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        $request->patient->update([
            'privacy_last_exported_at' => now(config('app.timezone')),
        ]);

        $request->update([
            'status' => 'completed',
            'processed_at' => now(config('app.timezone')),
            'export_path' => $path,
            'result_snapshot' => [
                'exported_sections' => array_keys($snapshot),
                'record_totals' => [
                    'appointments' => count($snapshot['appointments']),
                    'clinical_records' => count($snapshot['clinical_records']),
                    'accounts_receivable' => count($snapshot['accounts_receivable']),
                    'document_acceptances' => count($snapshot['document_acceptances']),
                ],
            ],
            'last_error_message' => null,
        ]);

        return $request->refresh()->load(['unit', 'patient', 'requestedBy', 'processedBy']);
    }

    private function processAnonymization(PrivacyRequest $request): PrivacyRequest
    {
        DB::transaction(function () use ($request): void {
            $patient = $request->patient;
            $anonymizedName = "Paciente anonimizado #{$patient->id}";

            $patient->update([
                'name' => $anonymizedName,
                'preferred_name' => null,
                'birth_date' => null,
                'gender' => null,
                'marital_status' => null,
                'cpf' => null,
                'rg' => null,
                'email' => null,
                'phone' => null,
                'whatsapp' => null,
                'occupation' => null,
                'emergency_contact_name' => null,
                'emergency_contact_phone' => null,
                'zip_code' => null,
                'street' => null,
                'number' => null,
                'complement' => null,
                'district' => null,
                'city' => null,
                'state' => null,
                'avatar_path' => null,
                'observations' => null,
                'whatsapp_opt_in' => false,
                'whatsapp_opt_in_at' => null,
                'is_active' => false,
                'anonymized_at' => now(config('app.timezone')),
                'meta' => array_merge($patient->meta ?? [], [
                    'lgpd_anonymized_at' => now(config('app.timezone'))->toDateTimeString(),
                    'lgpd_request_id' => $request->id,
                ]),
            ]);

            foreach ($patient->guardians as $guardian) {
                $guardian->update([
                    'name' => "Responsavel anonimizado #{$guardian->id}",
                    'relationship' => null,
                    'document' => null,
                    'email' => null,
                    'phone' => null,
                    'whatsapp' => null,
                    'address' => null,
                    'notes' => null,
                ]);
            }

            if ($patient->user) {
                $patient->user->update([
                    'name' => $anonymizedName,
                    'email' => null,
                    'phone' => null,
                    'document' => null,
                    'is_active' => false,
                    'meta' => array_merge($patient->user->meta ?? [], [
                        'lgpd_anonymized_at' => now(config('app.timezone'))->toDateTimeString(),
                        'lgpd_request_id' => $request->id,
                    ]),
                ]);
            }

            $patient->subscriptions()->delete();
        });

        $request->update([
            'status' => 'completed',
            'processed_at' => now(config('app.timezone')),
            'result_snapshot' => [
                'anonymized_modules' => [
                    'patient_registration',
                    'patient_guardians',
                    'portal_user_access',
                    'pwa_subscriptions',
                ],
                'retained_modules' => [
                    'clinical_records',
                    'financial_records',
                    'document_acceptances',
                    'fiscal_records',
                ],
            ],
            'last_error_message' => null,
        ]);

        return $request->refresh()->load(['unit', 'patient', 'requestedBy', 'processedBy']);
    }

    private function buildPatientSnapshot(Patient $patient): array
    {
        return [
            'exported_at' => now(config('app.timezone'))->toIso8601String(),
            'patient' => $patient->only([
                'id',
                'unit_id',
                'name',
                'preferred_name',
                'birth_date',
                'gender',
                'marital_status',
                'cpf',
                'rg',
                'email',
                'phone',
                'whatsapp',
                'zip_code',
                'street',
                'number',
                'complement',
                'district',
                'city',
                'state',
                'last_visit_at',
                'privacy_last_exported_at',
                'anonymized_at',
                'created_at',
                'updated_at',
            ]),
            'guardians' => $patient->guardians->map->toArray()->values()->all(),
            'appointments' => $patient->appointments->map->toArray()->values()->all(),
            'clinical_records' => $patient->clinicalRecords->map->toArray()->values()->all(),
            'treatment_plans' => $patient->treatmentPlans->map->toArray()->values()->all(),
            'accounts_receivable' => $patient->accountsReceivable->map->toArray()->values()->all(),
            'document_acceptances' => $patient->documentAcceptances->map(function ($acceptance): array {
                return array_merge(
                    $acceptance->toArray(),
                    ['document_template' => $acceptance->documentTemplate?->only(['id', 'name', 'slug', 'category'])]
                );
            })->values()->all(),
            'fiscal_invoices' => $patient->fiscalInvoices->map->toArray()->values()->all(),
            'portal_user' => $patient->user?->only([
                'id',
                'name',
                'email',
                'phone',
                'document',
                'is_active',
                'created_at',
                'updated_at',
            ]),
            'lgpd_notice' => [
                'anonymization_scope' => 'A anonimizacao automatica deste sistema prioriza cadastro e canais de contato, preservando registros clinicos, financeiros, documentais e fiscais sob base legal de retencao.',
            ],
        ];
    }

    private function scopeRequests(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function scopePatients(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }
}
