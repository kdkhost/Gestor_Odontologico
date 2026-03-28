<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceClaimBatch extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'guide_count' => 'integer',
            'claimed_total' => 'decimal:2',
            'approved_total' => 'decimal:2',
            'received_total' => 'decimal:2',
            'gloss_total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'processed_at' => 'datetime',
            'paid_at' => 'datetime',
            'response_payload' => 'array',
            'meta' => 'array',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function insurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function guides(): HasMany
    {
        return $this->hasMany(InsuranceClaimGuide::class);
    }
}
