<?php

namespace App\Filament\Resources\Professionals;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\Professionals\Pages\ManageProfessionals;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProfessionalResource extends ClinicResource
{
    protected static ?string $model = Professional::class;

    protected static string $permissionPrefix = 'profissionais';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Profissionais';

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Usuário')
                ->options(User::query()->where('user_type', 'staff')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('license_type')->label('Tipo de registro')->default('CRO'),
            TextInput::make('license_number')->label('Número do registro'),
            TextInput::make('specialty')->label('Especialidade'),
            TextInput::make('agenda_color')->label('Cor da agenda')->default('#2563eb'),
            TextInput::make('commission_percentage')->label('Comissão (%)')->numeric()->default(0),
            Textarea::make('bio')->label('Biografia'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->label('Profissional')->searchable()->sortable(),
            TextColumn::make('specialty')->label('Especialidade'),
            TextColumn::make('license_number')->label('Registro'),
            TextColumn::make('unit.name')->label('Unidade'),
            TextColumn::make('commission_percentage')->label('Comissão (%)'),
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
            'index' => ManageProfessionals::route('/'),
        ];
    }
}
