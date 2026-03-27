<?php

namespace App\Filament\Resources\InsurancePlans;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\InsurancePlans\Pages\ManageInsurancePlans;
use App\Models\InsurancePlan;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InsurancePlanResource extends ClinicResource
{
    protected static ?string $model = InsurancePlan::class;

    protected static string $permissionPrefix = 'pacientes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Convênios';

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('name')->label('Nome')->required(),
            TextInput::make('code')->label('Código'),
            TextInput::make('default_discount_percentage')->label('Desconto padrão (%)')->numeric()->default(0),
            TextInput::make('grace_days')->label('Carência (dias)')->numeric()->default(0),
            Textarea::make('coverage_notes')->label('Cobertura e observações'),
            KeyValue::make('settings')->label('Regras internas'),
            Toggle::make('is_active')->label('Ativo')->default(true),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Convênio')->searchable()->sortable(),
            TextColumn::make('code')->label('Código'),
            TextColumn::make('unit.name')->label('Unidade'),
            TextColumn::make('default_discount_percentage')->label('Desconto'),
            IconColumn::make('is_active')->label('Ativo')->boolean(),
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
            'index' => ManageInsurancePlans::route('/'),
        ];
    }
}
