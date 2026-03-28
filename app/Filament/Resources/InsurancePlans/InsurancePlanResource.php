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

    protected static ?string $navigationLabel = 'Convenios';

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('name')->label('Nome')->required(),
            TextInput::make('code')->label('Codigo'),
            TextInput::make('ans_registration')->label('Registro ANS'),
            TextInput::make('operator_document')->label('Documento da operadora'),
            TextInput::make('default_discount_percentage')->label('Desconto padrao (%)')->numeric()->default(0),
            TextInput::make('grace_days')->label('Carencia (dias)')->numeric()->default(0),
            Toggle::make('requires_authorization')->label('Exige autorizacao')->default(false),
            TextInput::make('authorization_valid_days')->label('Validade da autorizacao (dias)')->numeric()->default(30),
            TextInput::make('settlement_days')->label('Prazo medio de fechamento (dias)')->numeric()->default(30),
            Select::make('submission_channel')->label('Canal padrao')->options([
                'manual' => 'Manual',
                'email' => 'E-mail',
                'portal' => 'Portal da operadora',
                'api' => 'API',
                'tiss' => 'TISS',
            ]),
            TextInput::make('tiss_table_code')->label('Tabela TISS padrao'),
            Textarea::make('coverage_notes')->label('Cobertura e observacoes'),
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
            TextColumn::make('name')->label('Convenio')->searchable()->sortable(),
            TextColumn::make('code')->label('Codigo'),
            TextColumn::make('unit.name')->label('Unidade'),
            IconColumn::make('requires_authorization')->label('Exige guia')->boolean(),
            TextColumn::make('submission_channel')->label('Canal'),
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
