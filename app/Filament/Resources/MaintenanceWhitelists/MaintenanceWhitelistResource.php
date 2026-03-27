<?php

namespace App\Filament\Resources\MaintenanceWhitelists;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\MaintenanceWhitelists\Pages\ManageMaintenanceWhitelists;
use App\Models\MaintenanceWhitelist;
use App\Models\Unit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenanceWhitelistResource extends ClinicResource
{
    protected static ?string $model = MaintenanceWhitelist::class;

    protected static string $permissionPrefix = 'manutencao';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Whitelist da manutenção';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')
                ->label('Unidade')
                ->options(Unit::query()->pluck('name', 'id'))
                ->searchable()
                ->preload(),
            Select::make('user_id')
                ->label('Usuário')
                ->options(User::query()->pluck('name', 'id'))
                ->searchable()
                ->preload(),
            Select::make('type')
                ->label('Tipo')
                ->options([
                    'ip' => 'IP',
                    'device' => 'Dispositivo',
                    'user' => 'Usuário',
                ])
                ->required(),
            TextInput::make('value')->label('Valor liberado')->required()->maxLength(255),
            DateTimePicker::make('expires_at')->label('Expira em'),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
            Toggle::make('is_active')->label('Ativo')->default(true),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('value')->label('Valor')->searchable()->copyable(),
                TextColumn::make('user.name')->label('Usuário')->toggleable(),
                TextColumn::make('unit.name')->label('Unidade')->toggleable(),
                TextColumn::make('expires_at')->label('Expira em')->dateTime('d/m/Y H:i'),
                IconColumn::make('is_active')->label('Ativo')->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMaintenanceWhitelists::route('/'),
        ];
    }
}
