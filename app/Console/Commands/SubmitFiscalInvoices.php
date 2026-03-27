<?php

namespace App\Console\Commands;

use App\Services\FiscalInvoiceService;
use Illuminate\Console\Command;

class SubmitFiscalInvoices extends Command
{
    protected $signature = 'clinic:nfse-submit {--unit_id=} {--limit=20}';

    protected $description = 'Processa a fila de NFSe pendente e gera protocolo de envio.';

    public function handle(FiscalInvoiceService $service): int
    {
        $unitId = $this->option('unit_id');
        $limit = max(1, (int) $this->option('limit'));

        $count = $service->submitPending(
            unitId: filled($unitId) ? (int) $unitId : null,
            limit: $limit,
        );

        if ($count === 0) {
            $this->warn('Nenhuma NFSe pendente encontrada na fila fiscal.');

            return self::SUCCESS;
        }

        $this->info("{$count} NFSe(s) processada(s) para protocolo.");

        return self::SUCCESS;
    }
}
