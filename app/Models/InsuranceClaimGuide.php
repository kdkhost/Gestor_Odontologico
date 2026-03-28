<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceClaimGuide extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'claimed_total' => 'decimal:2',
            'approved_total' => 'decimal:2',
            'received_total' => 'decimal:2',
            'gloss_total' => 'decimal:2',
            'executed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaimBatch::class, 'insurance_claim_batch_id');
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(InsuranceAuthorization::class, 'insurance_authorization_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InsuranceClaimItem::class, 'insurance_claim_guide_id');
    }
}
