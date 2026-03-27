<?php

namespace App\Filament\Resources\Procedures;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\Procedures\Pages\ManageProcedures;
use App\Models\Procedure;
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

class ProcedureResource extends ClinicResource
{
    protected static ?string $model = Procedure::class;

    protected static string $permissionPrefix = 'procedimentos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Procedimentos';

    protected static string|\UnitEnum|null $navigationGroup = 'Clínico';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('name')->label('Nome')->required(),
            TextInput::make('code')->label('Código'),
            TextInput::make('category')->label('Categoria'),
            TextInput::make('default_price')->label('Preço padrão')->numeric()->prefix('R$'),
            TextInput::make('default_duration_minutes')->label('Duração (min)')->numeric()->default(60),
            Toggle::make('requires_approval')->label('Exige aprovação')->default(false),
            Toggle::make('is_active')->label('Ativo')->default(true),
            KeyValue::make('consumption_rules')->label('Regras de consumo'),
            Textarea::make('notes')->label('Observações'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Procedimento')->searchable()->sortable(),
            TextColumn::make('category')->label('Categoria'),
            TextColumn::make('default_price')->label('Preço'),
            TextColumn::make('default_duration_minutes')->label('Duração'),
            IconColumn::make('requires_approval')->label('Aprova')->boolean(),
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
            'index' => ManageProcedures::route('/'),
        ];
    }
}
