<?php

namespace App\Filament\Resources\CommissionSettlements;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\CommissionSettlements\Pages\ManageCommissionSettlements;
use App\Models\CommissionSettlement;
use App\Services\CommissionSettlementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommissionSettlementResource extends ClinicResource
{
    protected static ?string $model = CommissionSettlement::class;

    protected static string $permissionPrefix = 'financeiro';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Repasses detalhados';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CommissionSettlement::query()
            ->where(function ($query): void {
                $query->where('status', 'closed')
                    ->orWhere(function ($builder): void {
                        $builder->where('status', 'paid')
                            ->whereNull('reconciled_at');
                    });
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() !== null ? 'warning' : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('reference')->label('Referência')->disabled(),
            TextInput::make('status')->label('Status')->disabled(),
            TextInput::make('gross_amount')->label('Valor bruto')->prefix('R$')->disabled(),
            Select::make('payment_method')
                ->label('Forma do repasse')
                ->options(self::paymentMethodOptions()),
            TextInput::make('payment_reference')->label('Referência bancária'),
            FileUpload::make('proof_path')
                ->label('Comprovante do repasse')
                ->disk('public')
                ->directory('commission-proofs')
                ->downloadable()
                ->openable()
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']),
            TextInput::make('bank_statement_reference')->label('Referência no extrato'),
            Textarea::make('notes')->label('Observações do fechamento'),
            Textarea::make('reconciliation_notes')->label('Observações da conciliação'),
            DateTimePicker::make('paid_at')->label('Pago em')->seconds(false)->disabled(),
            DateTimePicker::make('reconciled_at')->label('Conciliado em')->seconds(false)->disabled(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('closed_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Referência')->searchable(),
                TextColumn::make('professional.user.name')->label('Profissional')->searchable(),
                TextColumn::make('unit.name')->label('Unidade')->placeholder('Sem unidade'),
                TextColumn::make('gross_amount')->label('Valor')->money('BRL'),
                TextColumn::make('payment_method')->label('Forma')->placeholder('-'),
                TextColumn::make('payment_reference')->label('Ref. bancária')->limit(24)->placeholder('-'),
                TextColumn::make('proof_path')
                    ->label('Comprovante')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Anexado' : 'Pendente')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'gray'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('reconciled_at')
                    ->label('Conciliação')
                    ->formatStateUsing(fn ($state): string => $state ? 'Conciliado' : 'Pendente')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'warning'),
                TextColumn::make('paidBy.name')->label('Pago por')->placeholder('-')->toggleable(),
                TextColumn::make('reconciledBy.name')->label('Conciliado por')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('registerPayment')
                    ->label('Registrar pagamento')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (CommissionSettlement $record): bool => $record->status === 'closed')
                    ->form([
                        Select::make('payment_method')
                            ->label('Forma do repasse')
                            ->options(self::paymentMethodOptions())
                            ->required(),
                        TextInput::make('payment_reference')->label('Referência bancária'),
                        FileUpload::make('proof_path')
                            ->label('Comprovante')
                            ->disk('public')
                            ->directory('commission-proofs')
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']),
                        DateTimePicker::make('paid_at')->label('Pago em')->seconds(false),
                        Textarea::make('notes')->label('Observações do pagamento'),
                    ])
                    ->action(function (CommissionSettlement $record, array $data): void {
                        app(CommissionSettlementService::class)->registerPayment($record, [
                            ...$data,
                            'paid_by_user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Pagamento do repasse registrado.')
                            ->success()
                            ->send();
                    }),
                Action::make('reconcileSettlement')
                    ->label('Conciliar')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (CommissionSettlement $record): bool => $record->status === 'paid' && $record->reconciled_at === null)
                    ->form([
                        TextInput::make('bank_statement_reference')->label('Referência no extrato'),
                        DateTimePicker::make('reconciled_at')->label('Conciliado em')->seconds(false),
                        Textarea::make('reconciliation_notes')->label('Observações da conciliação'),
                    ])
                    ->action(function (CommissionSettlement $record, array $data): void {
                        app(CommissionSettlementService::class)->markAsReconciled($record, [
                            ...$data,
                            'reconciled_by_user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Repasse conciliado com sucesso.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCommissionSettlements::route('/'),
        ];
    }

    private static function paymentMethodOptions(): array
    {
        return [
            'manual' => 'Manual / não informado',
            'pix' => 'PIX',
            'transferencia' => 'Transferência',
            'ted' => 'TED',
            'doc' => 'DOC',
            'boleto' => 'Boleto',
            'dinheiro' => 'Dinheiro',
            'outro' => 'Outro',
        ];
    }
}
