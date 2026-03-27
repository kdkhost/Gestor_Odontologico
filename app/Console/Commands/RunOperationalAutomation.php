<?php

namespace App\Console\Commands;

use App\Services\OperationalAutomationService;
use Illuminate\Console\Command;

class RunOperationalAutomation extends Command
{
    protected $signature = 'clinic:automation-run {--dry-run : Apenas simula os disparos sem enviar mensagens}';

    protected $description = 'Executa a régua operacional automática da clínica.';

    public function handle(OperationalAutomationService $automation): int
    {
        $results = $automation->runAll((bool) $this->option('dry-run'));

        foreach ($results as $type => $summary) {
            $this->info(sprintf(
                '%s | status=%s | matched=%d | sent=%d | skipped=%d | failed=%d',
                $type,
                $summary['status'] ?? 'unknown',
                (int) ($summary['matched_count'] ?? 0),
                (int) ($summary['sent_count'] ?? 0),
                (int) ($summary['skipped_count'] ?? 0),
                (int) ($summary['failed_count'] ?? 0),
            ));
        }

        return self::SUCCESS;
    }
}
