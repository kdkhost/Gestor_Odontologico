<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Services\AppointmentConflictService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (Appointment $appointment): void {
            app(AppointmentConflictService::class)->validate($appointment);
        });
    }

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'check_in_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'anamnesis' => 'array',
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

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function chair(): BelongsTo
    {
        return $this->belongsTo(Chair::class);
    }

    public function insurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class);
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function clinicalRecords(): HasMany
    {
        return $this->hasMany(ClinicalRecord::class);
    }

    public function accountReceivable(): HasOne
    {
        return $this->hasOne(AccountReceivable::class);
    }
}
