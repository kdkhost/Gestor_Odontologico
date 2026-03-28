<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use LogsModelActivity;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'last_visit_at' => 'datetime',
            'privacy_last_exported_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'is_active' => 'boolean',
            'whatsapp_opt_in' => 'boolean',
            'whatsapp_opt_in_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(PatientGuardian::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function clinicalRecords(): HasMany
    {
        return $this->hasMany(ClinicalRecord::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function accountsReceivable(): HasMany
    {
        return $this->hasMany(AccountReceivable::class);
    }

    public function latestAppointment(): HasOne
    {
        return $this->hasOne(Appointment::class)->latestOfMany('scheduled_start');
    }

    public function documentAcceptances(): HasMany
    {
        return $this->hasMany(DocumentAcceptance::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(PwaSubscription::class);
    }

    public function fiscalInvoices(): HasMany
    {
        return $this->hasMany(FiscalInvoice::class);
    }

    public function privacyRequests(): HasMany
    {
        return $this->hasMany(PrivacyRequest::class);
    }

    public function insuranceAuthorizations(): HasMany
    {
        return $this->hasMany(InsuranceAuthorization::class);
    }

    public function insuranceClaimGuides(): HasMany
    {
        return $this->hasMany(InsuranceClaimGuide::class);
    }
}
