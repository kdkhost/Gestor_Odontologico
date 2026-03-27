<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessScheduleRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'allowed_ip_list' => 'array',
            'allowed_device_hashes' => 'array',
            'allow_outside_window' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
