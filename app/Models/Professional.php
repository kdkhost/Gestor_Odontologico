<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professional extends Model
{
    use LogsModelActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'commission_percentage' => 'decimal:2',
            'settings' => 'array',
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

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function clinicalRecords(): HasMany
    {
        return $this->hasMany(ClinicalRecord::class);
    }

    public function commissionEntries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class);
    }

    public function performanceTargets(): HasMany
    {
        return $this->hasMany(PerformanceTarget::class);
    }

    public function commissionSettlements(): HasMany
    {
        return $this->hasMany(CommissionSettlement::class);
    }

    public function insuranceAuthorizations(): HasMany
    {
        return $this->hasMany(InsuranceAuthorization::class);
    }
}
