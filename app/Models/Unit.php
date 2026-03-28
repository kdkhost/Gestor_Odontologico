<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use LogsModelActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function chairs(): HasMany
    {
        return $this->hasMany(Chair::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function professionals(): HasMany
    {
        return $this->hasMany(Professional::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function settingsRecords(): HasMany
    {
        return $this->hasMany(SystemSetting::class);
    }

    public function performanceTargets(): HasMany
    {
        return $this->hasMany(PerformanceTarget::class);
    }

    public function commissionSettlements(): HasMany
    {
        return $this->hasMany(CommissionSettlement::class);
    }

    public function bankStatementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class);
    }

    public function fiscalInvoices(): HasMany
    {
        return $this->hasMany(FiscalInvoice::class);
    }

    public function privacyRequests(): HasMany
    {
        return $this->hasMany(PrivacyRequest::class);
    }
}
