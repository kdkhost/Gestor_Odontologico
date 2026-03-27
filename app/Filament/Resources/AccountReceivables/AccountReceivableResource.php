<?php

namespace App\Filament\Resources\AccountReceivables;

use App\Filament\Resources\AccountReceivables\Pages\ManageAccountReceivables;
use App\Filament\Resources\ClinicResource;
use App\Models\AccountReceivable;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountReceivableResource extends ClinicResource
{
    protected static ?string $model = AccountReceivable::class;

    protected static string $permissionPrefix = 'financeiro';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Contas a receber';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->required()->searchable()->preload(),
            Select::make('patient_id')->label('Paciente')->options(Patient::query()->pluck('name', 'id'))->required()->searchable()->preload(),
            Select::make('treatment_plan_id')->label('Plano')->options(TreatmentPlan::query()->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('reference')->label('Referência'),
            TextInput::make('description')->label('Descrição')->required(),
            Select::make('status')->label('Status')->options([
                'open' => 'Em aberto',
                'partial' => 'Parcial',
                'paid' => 'Pago',
                'cancelled' => 'Cancelado',
                'overdue' => 'Vencido',
            ])->required(),
            TextInput::make('total_amount')->label('Valor bruto')->numeric()->prefix('R$'),
            TextInput::make('discount_amount')->label('Desconto')->numeric()->prefix('R$')->default(0),
            TextInput::make('interest_amount')->label('Juros')->numeric()->prefix('R$')->default(0),
            TextInput::make('fine_amount')->label('Multa')->numeric()->prefix('R$')->default(0),
            TextInput::make('net_amount')->label('Valor líquido')->numeric()->prefix('R$'),
            DatePicker::make('due_date')->label('Vencimento'),
            KeyValue::make('meta')->label('Metadados financeiros'),
            Repeater::make('installments')
                ->relationship()
                ->label('Parcelas')
                ->schema([
                    TextInput::make('installment_number')->label('Número')->numeric()->required(),
                    DatePicker::make('due_date')->required(),
                    TextInput::make('amount')->label('Valor')->numeric()->prefix('R$')->required(),
                    TextInput::make('balance')->label('Saldo')->numeric()->prefix('R$')->required(),
                    Select::make('status')->options([
                        'open' => 'Em aberto',
                        'paid' => 'Pago',
                        'overdue' => 'Vencido',
                        'cancelled' => 'Cancelado',
                    ])->required(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('description')->label('Descrição')->searchable(),
            TextColumn::make('patient.name')->label('Paciente')->searchable(),
            TextColumn::make('unit.name')->label('Unidade'),
            TextColumn::make('status')->badge(),
            TextColumn::make('net_amount')->label('Líquido'),
            TextColumn::make('due_date')->label('Vencimento')->date('d/m/Y'),
        ])->recordActions([
            ViewAction::make(),
            EditAction::make(),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAccountReceivables::route('/'),
        ];
    }
}
