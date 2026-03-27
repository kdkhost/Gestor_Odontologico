<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use LogsModelActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'meta' => 'array',
            'is_active' => 'boolean',
            'requires_opt_in' => 'boolean',
            'requires_official_window' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function whatsappDispatchLogs(): HasMany
    {
        return $this->hasMany(WhatsAppDispatchLog::class);
    }
}
