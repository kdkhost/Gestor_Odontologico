<?php

namespace App\Console\Commands;

use App\Services\InsuranceAuthorizationService;
use Illuminate\Console\Command;

class ExpireInsuranceAuthorizations extends Command
{
    protected $signature = 'clinic:insurance-authorizations-expire {--unit_id=}';

    protected $description = 'Expira guias de convenio com validade vencida';

    public function handle(InsuranceAuthorizationService $service): int
    {
        $count = $service->markExpired(
            $this->option('unit_id') ? (int) $this->option('unit_id') : null,
        );

        $this->info("Guias expiradas: {$count}");

        return self::SUCCESS;
    }
}
