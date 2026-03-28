<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceAuthorization extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_total' => 'decimal:2',
            'authorized_total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'authorized_at' => 'datetime',
            'response_due_at' => 'datetime',
            'valid_until' => 'datetime',
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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InsuranceAuthorizationItem::class);
    }
}
