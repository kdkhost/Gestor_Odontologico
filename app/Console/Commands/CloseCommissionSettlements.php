<?php

namespace App\Console\Commands;

use App\Services\CommissionSettlementService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseCommissionSettlements extends Command
{
    protected $signature = 'clinic:commission-close
        {--from= : Data inicial no formato YYYY-MM-DD}
        {--to= : Data final no formato YYYY-MM-DD}
        {--unit_id= : Unidade para limitar o fechamento}';

    protected $description = 'Fecha repasses de comissão pendentes por profissional no período informado.';

    public function handle(CommissionSettlementService $settlements): int
    {
        $from = $this->option('from')
            ? Carbon::parse((string) $this->option('from'), config('app.timezone'))->startOfDay()
            : now(config('app.timezone'))->startOfMonth();

        $to = $this->option('to')
            ? Carbon::parse((string) $this->option('to'), config('app.timezone'))->endOfDay()
            : now(config('app.timezone'))->endOfMonth();

        $count = $settlements->closeAllPending(
            unitId: $this->option('unit_id') ? (int) $this->option('unit_id') : null,
            fromDate: $from,
            toDate: $to,
        );

        $this->info("Repasses fechados: {$count}");

        return self::SUCCESS;
    }
}
