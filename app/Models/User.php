<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasRoles;
    use LogsModelActivity;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'document',
        'unit_id',
        'user_type',
        'preferred_theme',
        'is_active',
        'meta',
        'last_login_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'meta' => 'array',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->user_type !== 'patient';
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function professional(): HasOne
    {
        return $this->hasOne(Professional::class);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function accessScheduleRules(): HasMany
    {
        return $this->hasMany(AccessScheduleRule::class);
    }

    public function maintenanceWhitelists(): HasMany
    {
        return $this->hasMany(MaintenanceWhitelist::class);
    }

    public function createdCommissionSettlements(): HasMany
    {
        return $this->hasMany(CommissionSettlement::class, 'created_by_user_id');
    }

    public function uploadedBankStatementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class, 'uploaded_by_user_id');
    }

    public function createdFiscalInvoices(): HasMany
    {
        return $this->hasMany(FiscalInvoice::class, 'created_by_user_id');
    }

    public function requestedPrivacyRequests(): HasMany
    {
        return $this->hasMany(PrivacyRequest::class, 'requested_by_user_id');
    }

    public function processedPrivacyRequests(): HasMany
    {
        return $this->hasMany(PrivacyRequest::class, 'processed_by_user_id');
    }

    public function createdInsuranceAuthorizations(): HasMany
    {
        return $this->hasMany(InsuranceAuthorization::class, 'created_by_user_id');
    }

    public function createdInsuranceClaimBatches(): HasMany
    {
        return $this->hasMany(InsuranceClaimBatch::class, 'created_by_user_id');
    }

    public function submittedInsuranceClaimBatches(): HasMany
    {
        return $this->hasMany(InsuranceClaimBatch::class, 'submitted_by_user_id');
    }
}
