<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRunLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'matched_count' => 'integer',
            'sent_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
            'payload' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
