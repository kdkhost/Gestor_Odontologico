<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceAuthorizationItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:2',
            'authorized_quantity' => 'decimal:2',
            'requested_amount' => 'decimal:2',
            'authorized_amount' => 'decimal:2',
            'valid_until' => 'datetime',
            'executed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(InsuranceAuthorization::class, 'insurance_authorization_id');
    }

    public function treatmentPlanItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function claimItems(): HasMany
    {
        return $this->hasMany(InsuranceClaimItem::class, 'insurance_authorization_item_id');
    }
}
