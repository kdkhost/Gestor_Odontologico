<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Unit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends ClinicResource
{
    protected static ?string $model = User::class;

    protected static string $permissionPrefix = 'usuarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Usuários';

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome')->required()->maxLength(120),
            TextInput::make('email')->email()->maxLength(120),
            TextInput::make('phone')->label('Celular')->mask('(99) 99999-9999'),
            TextInput::make('document')->label('Documento')->mask('999.999.999-99'),
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->searchable()->preload(),
            Select::make('user_type')->label('Tipo')->options([
                'staff' => 'Colaborador',
                'patient' => 'Paciente',
            ])->required(),
            Select::make('roles')
                ->relationship('roles', 'name')
                ->label('Papéis')
                ->multiple()
                ->preload(),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn (?string $state) => filled($state)),
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
            TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            TextColumn::make('email')->label('E-mail')->searchable(),
            TextColumn::make('phone')->label('Celular'),
            TextColumn::make('unit.name')->label('Unidade'),
            TextColumn::make('roles.name')->label('Papéis')->badge()->separator(','),
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
            'index' => ManageUsers::route('/'),
        ];
    }
}
