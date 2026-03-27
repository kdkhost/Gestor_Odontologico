<?php

namespace App\Filament\Resources\TreatmentPlans;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\TreatmentPlans\Pages\ManageTreatmentPlans;
use App\Models\InsurancePlan;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\TreatmentPlan;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TreatmentPlanResource extends ClinicResource
{
    protected static ?string $model = TreatmentPlan::class;

    protected static string $permissionPrefix = 'planos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboard;

    protected static ?string $navigationLabel = 'Planos de tratamento';

    protected static string|\UnitEnum|null $navigationGroup = 'Clínico';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->required()->searchable()->preload(),
            Select::make('patient_id')->label('Paciente')->options(Patient::query()->pluck('name', 'id'))->required()->searchable()->preload(),
            Select::make('professional_id')->label('Profissional')->options(Professional::query()->with('user')->get()->pluck('user.name', 'id'))->searchable()->preload(),
            Select::make('insurance_plan_id')->label('Convênio')->options(InsurancePlan::query()->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('code')->label('Código'),
            TextInput::make('name')->label('Nome do plano')->required(),
            Select::make('status')->label('Status')->options([
                'draft' => 'Rascunho',
                'approved' => 'Aprovado',
                'partial' => 'Parcial',
                'completed' => 'Concluído',
                'cancelled' => 'Cancelado',
            ])->required(),
            TextInput::make('total_amount')->label('Valor bruto')->numeric()->prefix('R$')->default(0),
            TextInput::make('discount_amount')->label('Desconto')->numeric()->prefix('R$')->default(0),
            TextInput::make('final_amount')->label('Valor final')->numeric()->prefix('R$')->default(0),
            DateTimePicker::make('approved_at')->label('Aprovado em'),
            DateTimePicker::make('expires_at')->label('Expira em'),
            Textarea::make('summary')->label('Resumo'),
            Textarea::make('notes')->label('Observações'),
            Repeater::make('items')
                ->relationship()
                ->label('Itens do plano')
                ->schema([
                    TextInput::make('description')->required(),
                    TextInput::make('tooth_code')->label('Dente'),
                    TextInput::make('face')->label('Face'),
                    TextInput::make('quantity')->numeric()->default(1),
                    TextInput::make('unit_price')->numeric()->prefix('R$')->default(0),
                    TextInput::make('total_price')->numeric()->prefix('R$')->default(0),
                    Select::make('status')->options([
                        'planned' => 'Planejado',
                        'approved' => 'Aprovado',
                        'done' => 'Executado',
                        'cancelled' => 'Cancelado',
                    ]),
                ])
                ->columnSpanFull()
                ->defaultItems(1),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Plano')->searchable()->sortable(),
            TextColumn::make('patient.name')->label('Paciente')->searchable(),
            TextColumn::make('professional.user.name')->label('Profissional'),
            TextColumn::make('status')->badge(),
            TextColumn::make('final_amount')->label('Valor final'),
            TextColumn::make('expires_at')->label('Validade')->dateTime('d/m/Y H:i'),
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
            'index' => ManageTreatmentPlans::route('/'),
        ];
    }
}
