<?php

namespace App\Console\Commands;

use App\Services\InsuranceClaimBillingService;
use Illuminate\Console\Command;

class CreateInsuranceClaimDraftBatches extends Command
{
    protected $signature = 'clinic:insurance-claims-create-drafts {--competence=} {--unit_id=} {--insurance_plan_id=}';

    protected $description = 'Cria lotes em rascunho para faturamento de convenio a partir de itens autorizados e executados';

    public function handle(InsuranceClaimBillingService $service): int
    {
        $competence = $this->option('competence') ?: now(config('app.timezone'))->format('Y-m');

        $count = $service->createDraftBatchesForCompetence(
            competenceMonth: $competence,
            unitId: $this->option('unit_id') ? (int) $this->option('unit_id') : null,
            insurancePlanId: $this->option('insurance_plan_id') ? (int) $this->option('insurance_plan_id') : null,
        );

        $this->info("Lotes criados: {$count}");

        return self::SUCCESS;
    }
}
