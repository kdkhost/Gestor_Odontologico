<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'amount' => 'decimal:2',
            'amount_absolute' => 'decimal:2',
            'matched_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function statementImport(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function suggestedSettlement(): BelongsTo
    {
        return $this->belongsTo(CommissionSettlement::class, 'suggested_commission_settlement_id');
    }

    public function matchedSettlement(): BelongsTo
    {
        return $this->belongsTo(CommissionSettlement::class, 'matched_commission_settlement_id');
    }
}
