<?php

namespace App\Console\Commands;

use App\Services\CommissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncCommissionEntries extends Command
{
    protected $signature = 'clinic:commission-sync {--from= : Data inicial no formato YYYY-MM-DD para limitar o reprocessamento}';

    protected $description = 'Reprocessa comissões a partir das contas a receber já pagas.';

    public function handle(CommissionService $commissions): int
    {
        $from = $this->option('from')
            ? Carbon::parse((string) $this->option('from'), config('app.timezone'))->startOfDay()
            : null;

        $count = $commissions->syncPaidSince($from);

        $this->info("Comissões sincronizadas: {$count}");

        return self::SUCCESS;
    }
}
