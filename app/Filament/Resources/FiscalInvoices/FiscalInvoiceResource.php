<?php

namespace App\Filament\Resources\FiscalInvoices;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\FiscalInvoices\Pages\ManageFiscalInvoices;
use App\Models\FiscalInvoice;
use App\Models\Unit;
use App\Services\FiscalInvoiceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use RuntimeException;

class FiscalInvoiceResource extends ClinicResource
{
    protected static ?string $model = FiscalInvoice::class;

    protected static string $permissionPrefix = 'financeiro';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Notas fiscais';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestao';

    protected static ?int $navigationSort = 8;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FiscalInvoice::query()
            ->whereNotIn('status', ['issued', 'cancelled'])
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
            Select::make('unit_id')
                ->label('Unidade')
                ->options(Unit::query()->orderBy('name')->pluck('name', 'id'))
                ->disabled()
                ->dehydrated(false),
            TextInput::make('reference')->label('Referencia')->disabled(),
            Select::make('provider_profile')
                ->label('Perfil do provedor')
                ->options(app(FiscalInvoiceService::class)->providerOptions())
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options(self::statusOptions())
                ->disabled()
                ->dehydrated(false),
            TextInput::make('city_code')->label('Codigo do municipio')->maxLength(20),
            TextInput::make('service_code')->label('Codigo do servico')->maxLength(30),
            TextInput::make('service_description')->label('Descricao do servico')->required()->maxLength(255),
            TextInput::make('amount')->label('Valor bruto')->numeric()->prefix('R$')->required()->disabled(),
            TextInput::make('deductions_amount')->label('Deducoes')->numeric()->prefix('R$')->default(0)->disabled(),
            TextInput::make('tax_base_amount')->label('Base tributavel')->numeric()->prefix('R$')->disabled(),
            TextInput::make('iss_rate')->label('Aliquota ISS')->numeric()->suffix('%')->disabled(),
            TextInput::make('iss_amount')->label('ISS apurado')->numeric()->prefix('R$')->disabled(),
            TextInput::make('rps_series')->label('Serie RPS')->maxLength(20),
            TextInput::make('rps_number')->label('Numero RPS')->maxLength(40),
            TextInput::make('external_reference')->label('Protocolo externo'),
            TextInput::make('municipal_invoice_number')->label('Numero NFSe municipal'),
            TextInput::make('verification_code')->label('Codigo de verificacao'),
            DatePicker::make('issue_date')->label('Data de emissao'),
            DateTimePicker::make('queued_at')->label('Enfileirada em')->seconds(false)->disabled(),
            DateTimePicker::make('submitted_at')->label('Protocolada em')->seconds(false)->disabled(),
            DateTimePicker::make('issued_at')->label('Emitida em')->seconds(false)->disabled(),
            DateTimePicker::make('cancelled_at')->label('Cancelada em')->seconds(false)->disabled(),
            KeyValue::make('customer_snapshot')->label('Snapshot do tomador')->columnSpanFull(),
            KeyValue::make('provider_payload')->label('Payload do provedor')->columnSpanFull(),
            KeyValue::make('provider_response')->label('Retorno do provedor')->columnSpanFull(),
            Textarea::make('last_error_message')->label('Ultima observacao / erro')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Referencia')->searchable(),
                TextColumn::make('patient.name')->label('Paciente')->searchable()->placeholder('Paciente'),
                TextColumn::make('unit.name')->label('Unidade')->searchable()->placeholder('Sem unidade'),
                TextColumn::make('amount')->label('Valor')->money('BRL'),
                TextColumn::make('service_code')->label('Servico')->placeholder('-')->toggleable(),
                TextColumn::make('provider_profile')->label('Provedor')->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_submission' => 'warning',
                        'submitted' => 'info',
                        'issued' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('rps_number')->label('RPS')->placeholder('-')->toggleable(),
                TextColumn::make('municipal_invoice_number')->label('NFSe')->placeholder('-'),
                TextColumn::make('issued_at')->label('Emitida em')->dateTime('d/m/Y H:i')->placeholder('-'),
            ])
            ->recordActions([
                Action::make('queueInvoice')
                    ->label('Enviar fila')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (FiscalInvoice $record): bool => $record->status === 'draft')
                    ->form([
                        TextInput::make('rps_series')->label('Serie RPS'),
                        TextInput::make('rps_number')->label('Numero RPS'),
                    ])
                    ->action(function (FiscalInvoice $record, array $data): void {
                        try {
                            app(FiscalInvoiceService::class)->queueInvoice($record, $data);

                            Notification::make()
                                ->title('NFSe enviada para fila fiscal.')
                                ->success()
                                ->send();
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('markAsIssued')
                    ->label('Marcar emitida')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (FiscalInvoice $record): bool => in_array($record->status, ['pending_submission', 'submitted'], true))
                    ->form([
                        TextInput::make('municipal_invoice_number')->label('Numero NFSe municipal'),
                        TextInput::make('verification_code')->label('Codigo de verificacao'),
                        TextInput::make('external_reference')->label('Protocolo externo'),
                        DatePicker::make('issue_date')->label('Data da nota'),
                        DateTimePicker::make('issued_at')->label('Emitida em')->seconds(false),
                    ])
                    ->action(function (FiscalInvoice $record, array $data): void {
                        try {
                            app(FiscalInvoiceService::class)->markAsIssued($record, $data);

                            Notification::make()
                                ->title('NFSe marcada como emitida.')
                                ->success()
                                ->send();
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('cancelInvoice')
                    ->label('Cancelar')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (FiscalInvoice $record): bool => $record->status !== 'cancelled')
                    ->form([
                        Textarea::make('reason')
                            ->label('Motivo do cancelamento')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (FiscalInvoice $record, array $data): void {
                        app(FiscalInvoiceService::class)->cancel($record, $data);

                        Notification::make()
                            ->title('NFSe cancelada com sucesso.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFiscalInvoices::route('/'),
        ];
    }

    private static function statusOptions(): array
    {
        return [
            'draft' => 'Rascunho',
            'pending_submission' => 'Na fila',
            'submitted' => 'Protocolada',
            'issued' => 'Emitida',
            'cancelled' => 'Cancelada',
        ];
    }
}
