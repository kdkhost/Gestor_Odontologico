<?php

namespace App\Services;

use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\CommissionSettlement;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BankStatementImportService
{
    public function fileTypeOptions(): array
    {
        return [
            'auto' => 'Detectar automaticamente',
            'csv' => 'CSV',
            'txt' => 'TXT delimitado',
            'ofx' => 'OFX',
        ];
    }

    public function bankProfileOptions(): array
    {
        return [
            'generic' => 'Generico',
            'itau' => 'Itau',
            'bradesco' => 'Bradesco',
            'santander' => 'Santander',
            'bb' => 'Banco do Brasil',
            'caixa' => 'Caixa',
            'sicredi' => 'Sicredi',
            'sicoob' => 'Sicoob',
            'inter' => 'Inter',
        ];
    }

    public function importStoredFile(string $storedPath, string $originalName, ?int $unitId = null, ?int $uploadedByUserId = null, array $options = []): BankStatementImport
    {
        $disk = $options['disk'] ?? 'local';
        $hasHeader = array_key_exists('has_header', $options) ? (bool) $options['has_header'] : true;
        $delimiterOption = (string) ($options['delimiter'] ?? 'auto');
        $fileType = $this->resolveFileType($originalName, (string) ($options['file_type'] ?? 'auto'));
        $bankProfile = (string) ($options['bank_profile'] ?? 'generic');

        return DB::transaction(function () use ($storedPath, $originalName, $unitId, $uploadedByUserId, $disk, $hasHeader, $delimiterOption, $fileType, $bankProfile) {
            $import = BankStatementImport::query()->create([
                'unit_id' => $unitId,
                'uploaded_by_user_id' => $uploadedByUserId,
                'original_name' => $originalName,
                'file_type' => $fileType,
                'stored_path' => $storedPath,
                'disk' => $disk,
                'delimiter' => $delimiterOption,
                'bank_profile' => $bankProfile,
                'has_header' => $hasHeader,
                'status' => 'processing',
            ]);

            try {
                [$rows, $meta] = $this->parseFile(
                    disk: $disk,
                    storedPath: $storedPath,
                    fileType: $fileType,
                    bankProfile: $bankProfile,
                    delimiterOption: $delimiterOption,
                    hasHeader: $hasHeader,
                );

                $suggestedCount = 0;
                $unmatchedCount = 0;

                foreach ($rows as $row) {
                    $match = $this->suggestSettlementForRow(
                        unitId: $unitId,
                        transactionDate: $row['transaction_date'],
                        amountAbsolute: $row['amount_absolute'],
                        transactionReference: $row['transaction_reference'],
                        description: $row['description'],
                    );

                    BankStatementLine::query()->create([
                        'bank_statement_import_id' => $import->id,
                        'line_number' => $row['line_number'],
                        'transaction_date' => $row['transaction_date'],
                        'description' => $row['description'],
                        'amount' => $row['amount'],
                        'amount_absolute' => $row['amount_absolute'],
                        'transaction_reference' => $row['transaction_reference'],
                        'suggested_commission_settlement_id' => $match['settlement_id'],
                        'match_score' => $match['score'],
                        'match_reason' => $match['reason'],
                        'raw_payload' => $row['raw_payload'],
                    ]);

                    if ($match['settlement_id']) {
                        $suggestedCount++;
                    } else {
                        $unmatchedCount++;
                    }
                }

                $import->update([
                    'status' => 'processed',
                    'file_type' => $fileType,
                    'delimiter' => $meta['delimiter'],
                    'bank_profile' => $bankProfile,
                    'total_lines' => count($rows),
                    'matched_suggestions_count' => $suggestedCount,
                    'unmatched_lines_count' => $unmatchedCount,
                    'reconciled_lines_count' => 0,
                    'imported_at' => now(config('app.timezone')),
                    'meta' => $meta,
                ]);
            } catch (\Throwable $exception) {
                $import->update([
                    'status' => 'failed',
                    'meta' => [
                        'error' => $exception->getMessage(),
                    ],
                ]);

                throw $exception;
            }

            return $import->fresh(['lines.suggestedSettlement.professional.user', 'unit', 'uploadedBy']);
        });
    }

    public function latestImports(?int $unitId, int $limit = 10): Collection
    {
        return $this->scopeImportQuery(
            BankStatementImport::query()->with(['unit', 'uploadedBy']),
            $unitId,
        )
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function openSuggestions(?int $unitId, int $limit = 25): Collection
    {
        return BankStatementLine::query()
            ->with([
                'statementImport.unit',
                'suggestedSettlement.professional.user',
                'suggestedSettlement.unit',
            ])
            ->whereNull('matched_commission_settlement_id')
            ->whereNotNull('suggested_commission_settlement_id')
            ->whereHas('suggestedSettlement', function (Builder $query) use ($unitId): void {
                $query->where('status', 'paid')
                    ->whereNull('reconciled_at');

                if ($unitId !== null) {
                    $query->where('unit_id', $unitId);
                }
            })
            ->orderByDesc('match_score')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function reconcileLine(BankStatementLine $line, ?int $reconciledByUserId = null): CommissionSettlement
    {
        $line->loadMissing(['statementImport', 'suggestedSettlement.professional.user']);

        if ($line->matched_commission_settlement_id !== null) {
            return $line->matchedSettlement()->firstOrFail();
        }

        $settlement = $line->suggestedSettlement;

        if (! $settlement) {
            throw new RuntimeException('Nenhuma sugestao de repasse disponivel para esta linha do extrato.');
        }

        if ($settlement->status !== 'paid' || $settlement->reconciled_at !== null) {
            throw new RuntimeException('O repasse sugerido nao esta elegivel para conciliacao automatica.');
        }

        $note = collect([
            $settlement->reconciliation_notes,
            'Conciliacao assistida pelo extrato '.$line->statementImport?->original_name.' na linha '.$line->line_number.'.',
        ])->filter()->implode("\n");

        $reconciledSettlement = app(CommissionSettlementService::class)->markAsReconciled($settlement, [
            'bank_statement_reference' => $line->transaction_reference ?: $settlement->bank_statement_reference,
            'reconciled_at' => $line->transaction_date ?: now(config('app.timezone')),
            'reconciled_by_user_id' => $reconciledByUserId,
            'reconciliation_notes' => $note,
        ]);

        $line->update([
            'matched_commission_settlement_id' => $reconciledSettlement->id,
            'matched_at' => now(config('app.timezone')),
        ]);

        $this->refreshImportCounters($line->statementImport()->firstOrFail());

        return $reconciledSettlement;
    }

    public function reconcileImportSuggestions(BankStatementImport $import, ?int $reconciledByUserId = null): int
    {
        $count = 0;
        $usedSettlementIds = [];

        $import->loadMissing(['lines.suggestedSettlement']);

        foreach ($import->lines as $line) {
            if ($line->matched_commission_settlement_id !== null || $line->suggested_commission_settlement_id === null) {
                continue;
            }

            if (in_array($line->suggested_commission_settlement_id, $usedSettlementIds, true)) {
                continue;
            }

            try {
                $this->reconcileLine($line, $reconciledByUserId);
                $usedSettlementIds[] = $line->suggested_commission_settlement_id;
                $count++;
            } catch (RuntimeException) {
                continue;
            }
        }

        $this->refreshImportCounters($import->fresh());

        return $count;
    }

    private function parseFile(string $disk, string $storedPath, string $fileType, string $bankProfile, string $delimiterOption, bool $hasHeader): array
    {
        $path = Storage::disk($disk)->path($storedPath);

        if (! is_file($path)) {
            throw new RuntimeException('Arquivo de extrato nao encontrado para importacao.');
        }

        if ($fileType === 'ofx') {
            return $this->parseOfxFile($disk, $storedPath, $bankProfile);
        }

        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException('Nao foi possivel abrir o arquivo de extrato.');
        }

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);

            return [[], [
                'delimiter' => $delimiterOption === 'auto' ? ';' : $this->resolveDelimiter($delimiterOption),
                'has_header' => $hasHeader,
                'headers' => [],
                'file_type' => $fileType,
                'bank_profile' => $bankProfile,
            ]];
        }

        $firstLine = $this->sanitizeLine($firstLine);
        $delimiter = $delimiterOption === 'auto'
            ? $this->detectDelimiter($firstLine)
            : $this->resolveDelimiter($delimiterOption);

        rewind($handle);

        $headers = [];
        $headerMap = [];
        $rows = [];
        $lineNumber = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_map(fn ($value) => is_string($value) ? $this->sanitizeLine($value) : $value, $row);

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            if ($lineNumber === 0 && $hasHeader) {
                $headers = $row;
                $headerMap = $this->buildHeaderMap($headers, $bankProfile);
                $lineNumber++;

                continue;
            }

            $lineNumber++;

            $normalizedRow = $this->normalizeRow(
                row: $row,
                headers: $headers,
                headerMap: $headerMap,
                hasHeader: $hasHeader,
            );

            if ($normalizedRow === null) {
                continue;
            }

            $rows[] = $normalizedRow + [
                'line_number' => $lineNumber,
                'raw_payload' => $row,
            ];
        }

        fclose($handle);

        return [$rows, [
            'delimiter' => $delimiter,
            'has_header' => $hasHeader,
            'headers' => $headers,
            'header_map' => $headerMap,
            'file_type' => $fileType,
            'bank_profile' => $bankProfile,
        ]];
    }

    private function normalizeRow(array $row, array $headers, array $headerMap, bool $hasHeader): ?array
    {
        $dateValue = $this->valueFromRow($row, $headers, $headerMap['date'] ?? null, $hasHeader, 0);
        $description = $this->valueFromRow($row, $headers, $headerMap['description'] ?? null, $hasHeader, 1);
        $amountValue = $this->valueFromRow($row, $headers, $headerMap['amount'] ?? null, $hasHeader, 2);
        $reference = $this->valueFromRow($row, $headers, $headerMap['reference'] ?? null, $hasHeader, 3);

        $amount = $this->parseAmount($amountValue);

        if ($amount === null) {
            return null;
        }

        return [
            'transaction_date' => $this->parseDate($dateValue),
            'description' => filled($description) ? trim((string) $description) : null,
            'amount' => $amount,
            'amount_absolute' => round(abs($amount), 2),
            'transaction_reference' => filled($reference) ? trim((string) $reference) : null,
        ];
    }

    private function suggestSettlementForRow(?int $unitId, ?CarbonInterface $transactionDate, float $amountAbsolute, ?string $transactionReference, ?string $description): array
    {
        $candidates = $this->scopeSettlementQuery(
            CommissionSettlement::query()->with(['professional.user', 'unit']),
            $unitId,
        )
            ->where('status', 'paid')
            ->whereNull('reconciled_at')
            ->whereBetween('gross_amount', [
                round(max(0, $amountAbsolute - 0.01), 2),
                round($amountAbsolute + 0.01, 2),
            ])
            ->get();

        if ($candidates->isEmpty()) {
            return [
                'settlement_id' => null,
                'score' => null,
                'reason' => null,
            ];
        }

        $descriptionAscii = Str::of((string) $description)->ascii()->lower()->value();
        $referenceAscii = Str::of((string) $transactionReference)->ascii()->lower()->value();

        $scored = $candidates->map(function (CommissionSettlement $candidate) use ($transactionDate, $descriptionAscii, $referenceAscii): array {
            $score = 50;
            $reasons = ['valor'];

            $settlementReference = Str::of((string) $candidate->reference)->ascii()->lower()->value();
            $paymentReference = Str::of((string) $candidate->payment_reference)->ascii()->lower()->value();
            $professionalName = Str::of((string) $candidate->professional?->user?->name)->ascii()->lower()->value();

            if ($referenceAscii !== '' && $referenceAscii === $paymentReference) {
                $score += 35;
                $reasons[] = 'referencia_pagamento';
            }

            if ($referenceAscii !== '' && $referenceAscii === $settlementReference) {
                $score += 25;
                $reasons[] = 'referencia_repasse';
            }

            if ($descriptionAscii !== '' && $paymentReference !== '' && str_contains($descriptionAscii, $paymentReference)) {
                $score += 20;
                $reasons[] = 'descricao_pagamento';
            }

            if ($descriptionAscii !== '' && $settlementReference !== '' && str_contains($descriptionAscii, $settlementReference)) {
                $score += 20;
                $reasons[] = 'descricao_repasse';
            }

            if ($descriptionAscii !== '' && $professionalName !== '' && str_contains($descriptionAscii, $professionalName)) {
                $score += 15;
                $reasons[] = 'descricao_profissional';
            }

            if ($transactionDate && $candidate->paid_at) {
                $days = abs($candidate->paid_at->startOfDay()->diffInDays(Carbon::instance($transactionDate)->startOfDay(), false));

                if ($days === 0) {
                    $score += 20;
                    $reasons[] = 'mesma_data';
                } elseif ($days <= 3) {
                    $score += 10;
                    $reasons[] = 'data_proxima';
                }
            }

            return [
                'settlement' => $candidate,
                'score' => $score,
                'reason' => implode(', ', $reasons),
            ];
        })->sortByDesc('score')->values();

        $best = $scored->first();
        $second = $scored->get(1);

        if (! $best) {
            return [
                'settlement_id' => null,
                'score' => null,
                'reason' => null,
            ];
        }

        $isReliable = $best['score'] >= 60
            && ($second === null || ($best['score'] - $second['score']) >= 15);

        return [
            'settlement_id' => $isReliable ? $best['settlement']->id : null,
            'score' => $best['score'],
            'reason' => $isReliable ? $best['reason'] : 'sugestao_ambigua',
        ];
    }

    private function refreshImportCounters(BankStatementImport $import): void
    {
        $import->update([
            'reconciled_lines_count' => $import->lines()->whereNotNull('matched_commission_settlement_id')->count(),
            'matched_suggestions_count' => $import->lines()->whereNotNull('suggested_commission_settlement_id')->count(),
            'unmatched_lines_count' => $import->lines()->whereNull('suggested_commission_settlement_id')->count(),
        ]);
    }

    private function scopeImportQuery(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function scopeSettlementQuery(Builder $query, ?int $unitId): Builder
    {
        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [';' => substr_count($line, ';'), ',' => substr_count($line, ','), "\t" => substr_count($line, "\t")];
        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }

    private function resolveDelimiter(string $delimiter): string
    {
        return match ($delimiter) {
            'tab' => "\t",
            ';', ',', "\t" => $delimiter,
            default => ';',
        };
    }

    private function sanitizeLine(?string $value): string
    {
        return trim(str_replace("\xEF\xBB\xBF", '', (string) $value));
    }

    private function rowIsEmpty(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function buildHeaderMap(array $headers, string $bankProfile): array
    {
        $normalized = collect($headers)->map(function ($header, $index): array {
            return [
                'index' => $index,
                'value' => Str::of((string) $header)
                    ->ascii()
                    ->lower()
                    ->replaceMatches('/[^a-z0-9]+/', '_')
                    ->trim('_')
                    ->value(),
            ];
        });

        $aliases = $this->headerAliasesForBankProfile($bankProfile);

        return [
            'date' => $this->findHeaderIndex($normalized, $aliases['date']),
            'description' => $this->findHeaderIndex($normalized, $aliases['description']),
            'amount' => $this->findHeaderIndex($normalized, $aliases['amount']),
            'reference' => $this->findHeaderIndex($normalized, $aliases['reference']),
        ];
    }

    private function parseOfxFile(string $disk, string $storedPath, string $bankProfile): array
    {
        $contents = Storage::disk($disk)->get($storedPath);
        $contents = str_replace("\r", '', $this->sanitizeLine($contents));

        $rows = [];
        $current = [];
        $insideTransaction = false;
        $lineNumber = 0;

        foreach (explode("\n", $contents) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (strtoupper($line) === '<STMTTRN>') {
                if ($insideTransaction && $current !== []) {
                    $lineNumber++;
                    $normalized = $this->normalizeOfxTransaction($current, $lineNumber);

                    if ($normalized !== null) {
                        $rows[] = $normalized;
                    }
                }

                $insideTransaction = true;
                $current = [];

                continue;
            }

            if (! $insideTransaction) {
                continue;
            }

            if (strtoupper($line) === '</STMTTRN>') {
                $lineNumber++;
                $normalized = $this->normalizeOfxTransaction($current, $lineNumber);

                if ($normalized !== null) {
                    $rows[] = $normalized;
                }

                $insideTransaction = false;
                $current = [];

                continue;
            }

            if (preg_match('/^<([A-Z0-9_]+)>(.*)$/', $line, $matches) === 1) {
                $tag = strtoupper($matches[1]);
                $value = trim($matches[2]);

                if ($value !== '') {
                    $current[$tag] = $value;
                }
            }
        }

        if ($insideTransaction && $current !== []) {
            $lineNumber++;
            $normalized = $this->normalizeOfxTransaction($current, $lineNumber);

            if ($normalized !== null) {
                $rows[] = $normalized;
            }
        }

        return [$rows, [
            'delimiter' => 'ofx',
            'has_header' => false,
            'headers' => [],
            'header_map' => [],
            'file_type' => 'ofx',
            'bank_profile' => $bankProfile,
            'parser' => 'ofx',
        ]];
    }

    private function normalizeOfxTransaction(array $transaction, int $lineNumber): ?array
    {
        $amount = $this->parseAmount($transaction['TRNAMT'] ?? null);

        if ($amount === null) {
            return null;
        }

        $description = collect([
            $transaction['MEMO'] ?? null,
            $transaction['NAME'] ?? null,
            $transaction['CHECKNUM'] ?? null,
        ])->filter()->implode(' | ');

        return [
            'line_number' => $lineNumber,
            'transaction_date' => $this->parseDate($transaction['DTPOSTED'] ?? null),
            'description' => $description !== '' ? $description : null,
            'amount' => $amount,
            'amount_absolute' => round(abs($amount), 2),
            'transaction_reference' => $transaction['FITID'] ?? $transaction['REFNUM'] ?? $transaction['CHECKNUM'] ?? null,
            'raw_payload' => $transaction,
        ];
    }

    private function headerAliasesForBankProfile(string $bankProfile): array
    {
        $generic = [
            'date' => ['data', 'date', 'data_movimento', 'data_lancamento', 'lancamento', 'data_pagamento', 'data_transacao'],
            'description' => ['descricao', 'description', 'historico', 'detalhe', 'complemento', 'memo'],
            'amount' => ['valor', 'amount', 'valor_rs', 'quantia', 'valor_lancamento'],
            'reference' => ['referencia', 'reference', 'documento', 'doc', 'codigo', 'identificador', 'fitid', 'numero_documento'],
        ];

        $profiles = [
            'itau' => [
                'date' => ['data_contabil'],
                'description' => ['descricao_lancamento', 'historico_lancamento'],
                'amount' => ['valor_lancamento', 'valor_movimento'],
                'reference' => ['id_lancamento', 'num_doc'],
            ],
            'bradesco' => [
                'date' => ['data_movto'],
                'description' => ['historico_lancamento', 'descricao_historico'],
                'amount' => ['valor_movto'],
                'reference' => ['numero_documento', 'seu_numero'],
            ],
            'santander' => [
                'date' => ['data_operacao'],
                'description' => ['descricao_operacao', 'historico_operacao'],
                'amount' => ['valor_operacao'],
                'reference' => ['codigo_transacao', 'referencia_operacao'],
            ],
            'bb' => [
                'date' => ['data_balanceamento'],
                'description' => ['historico_transacao'],
                'amount' => ['valor_transacao'],
                'reference' => ['numero_lancamento', 'numero_documento'],
            ],
            'caixa' => [
                'date' => ['data_lcto'],
                'description' => ['historico_lcto'],
                'amount' => ['valor_lcto'],
                'reference' => ['num_doc', 'referencia_bancaria'],
            ],
            'sicredi' => [
                'date' => ['data_movimento_conta'],
                'description' => ['descricao_movimento'],
                'amount' => ['valor_movimento_conta'],
                'reference' => ['documento_origem'],
            ],
            'sicoob' => [
                'date' => ['data_movimento_conta'],
                'description' => ['descricao_movimento_conta'],
                'amount' => ['valor_movimento_conta'],
                'reference' => ['numero_documento_banco'],
            ],
            'inter' => [
                'date' => ['data_compensacao'],
                'description' => ['descricao_completa'],
                'amount' => ['valor_original'],
                'reference' => ['referencia_pix', 'id_transacao'],
            ],
        ];

        $profile = $profiles[$bankProfile] ?? [];

        return [
            'date' => array_values(array_unique([...$generic['date'], ...($profile['date'] ?? [])])),
            'description' => array_values(array_unique([...$generic['description'], ...($profile['description'] ?? [])])),
            'amount' => array_values(array_unique([...$generic['amount'], ...($profile['amount'] ?? [])])),
            'reference' => array_values(array_unique([...$generic['reference'], ...($profile['reference'] ?? [])])),
        ];
    }

    private function resolveFileType(string $originalName, string $requestedType): string
    {
        if ($requestedType !== 'auto') {
            return $requestedType;
        }

        return match (Str::lower(pathinfo($originalName, PATHINFO_EXTENSION))) {
            'ofx' => 'ofx',
            'txt' => 'txt',
            default => 'csv',
        };
    }

    private function findHeaderIndex(Collection $normalizedHeaders, array $candidates): ?int
    {
        $match = $normalizedHeaders->first(fn (array $header): bool => in_array($header['value'], $candidates, true));

        return $match['index'] ?? null;
    }

    private function valueFromRow(array $row, array $headers, ?int $headerIndex, bool $hasHeader, int $fallbackIndex): ?string
    {
        if ($hasHeader && $headerIndex !== null) {
            return isset($row[$headerIndex]) ? trim((string) $row[$headerIndex]) : null;
        }

        return isset($row[$fallbackIndex]) ? trim((string) $row[$fallbackIndex]) : null;
    }

    private function parseAmount(?string $value): ?float
    {
        if (! filled($value)) {
            return null;
        }

        $normalized = preg_replace('/[^0-9,.\-]/', '', (string) $value);

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function parseDate(?string $value): ?CarbonInterface
    {
        if (! filled($value)) {
            return null;
        }

        $value = trim((string) $value);
        $value = preg_replace('/\[[^\]]+\]/', '', $value) ?? $value;
        $value = preg_replace('/\.[0-9]+$/', '', $value) ?? $value;

        $formats = ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd-m-Y', 'YmdHis', 'Ymd'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value, config('app.timezone'));
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
