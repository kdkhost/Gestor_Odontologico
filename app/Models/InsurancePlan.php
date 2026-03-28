<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePlan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'default_discount_percentage' => 'decimal:2',
            'requires_authorization' => 'boolean',
            'authorization_valid_days' => 'integer',
            'settlement_days' => 'integer',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(InsuranceAuthorization::class);
    }
}
