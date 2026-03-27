<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalInvoice extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'deductions_amount' => 'decimal:2',
            'tax_base_amount' => 'decimal:2',
            'iss_rate' => 'decimal:2',
            'iss_amount' => 'decimal:2',
            'issue_date' => 'date',
            'queued_at' => 'datetime',
            'submitted_at' => 'datetime',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'customer_snapshot' => 'array',
            'provider_payload' => 'array',
            'provider_response' => 'array',
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

    public function accountReceivable(): BelongsTo
    {
        return $this->belongsTo(AccountReceivable::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
