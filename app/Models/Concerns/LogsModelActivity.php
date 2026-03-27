<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;

trait LogsModelActivity
{
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->dontSubmitEmptyLogs();
    }
}
