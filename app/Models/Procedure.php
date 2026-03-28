<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procedure extends Model
{
    use LogsModelActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'requires_approval' => 'boolean',
            'consumption_rules' => 'array',
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

    public function treatmentPlanItems(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class);
    }

    public function insuranceAuthorizationItems(): HasMany
    {
        return $this->hasMany(InsuranceAuthorizationItem::class);
    }
}
