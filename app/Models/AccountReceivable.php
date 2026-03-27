<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Services\CommissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountReceivable extends Model
{
    use LogsModelActivity;

    protected $table = 'accounts_receivable';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(function (AccountReceivable $account): void {
            app(CommissionService::class)->syncForReceivable($account);
        });
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'fine_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function insurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function commissionEntries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class);
    }

    public function fiscalInvoices(): HasMany
    {
        return $this->hasMany(FiscalInvoice::class);
    }
}
