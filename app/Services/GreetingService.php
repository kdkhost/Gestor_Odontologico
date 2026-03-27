<?php

namespace App\Services;

use Carbon\CarbonInterface;

class GreetingService
{
    public function current(?CarbonInterface $now = null): string
    {
        $now ??= now(config('app.timezone'));
        $hour = (int) $now->format('H');

        return match (true) {
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };
    }
}
