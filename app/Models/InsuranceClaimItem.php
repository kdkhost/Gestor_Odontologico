<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceClaimItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
            'claimed_quantity' => 'decimal:2',
            'approved_quantity' => 'decimal:2',
            'claimed_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'gloss_amount' => 'decimal:2',
            'represented_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaimGuide::class, 'insurance_claim_guide_id');
    }

    public function authorizationItem(): BelongsTo
    {
        return $this->belongsTo(InsuranceAuthorizationItem::class, 'insurance_authorization_item_id');
    }

    public function treatmentPlanItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function representedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'represented_from_claim_item_id');
    }

    public function representations(): HasMany
    {
        return $this->hasMany(self::class, 'represented_from_claim_item_id');
    }
}
