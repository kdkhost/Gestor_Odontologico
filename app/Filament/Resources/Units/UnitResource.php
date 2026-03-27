<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\Units\Pages\ManageUnits;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class UnitResource extends ClinicResource
{
    protected static ?string $model = Unit::class;

    protected static string $permissionPrefix = 'unidades';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Unidades';

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('Nome')->required()->maxLength(120),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(120)
                    ->default(fn () => Str::slug('clinica-matriz'))
                    ->live(onBlur: true),
                TextInput::make('legal_name')->label('Razão social'),
                TextInput::make('document')->label('Documento')->mask('99.999.999/9999-99'),
                TextInput::make('email')->email(),
                TextInput::make('phone')->label('Telefone')->mask('(99) 9999-9999'),
                TextInput::make('whatsapp')->mask('(99) 99999-9999'),
                TextInput::make('zip_code')->label('CEP')->mask('99999-999'),
                TextInput::make('street')->label('Rua'),
                TextInput::make('number')->label('Número'),
                TextInput::make('complement')->label('Complemento'),
                TextInput::make('district')->label('Bairro'),
                TextInput::make('city')->label('Cidade'),
                TextInput::make('state')->label('UF')->maxLength(2),
                TextInput::make('municipal_registration')->label('Inscricao municipal'),
                TextInput::make('service_city_code')->label('Codigo municipio servico'),
                Select::make('nfse_provider_profile')
                    ->label('Perfil NFSe')
                    ->options([
                        'manual' => 'Manual',
                        'mock' => 'Mock / homologacao',
                    ])
                    ->default('manual')
                    ->required(),
                TextInput::make('default_service_code')->label('Codigo servico padrao'),
                TextInput::make('default_iss_rate')->label('Aliquota ISS padrao')->numeric()->suffix('%'),
                TextInput::make('rps_series')->label('Serie RPS'),
                TextInput::make('cnae_code')->label('CNAE'),
            ]),
            KeyValue::make('settings')->columnSpanFull(),
            Toggle::make('is_active')->label('Ativa')->default(true),
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
                TextColumn::make('name')->label('Unidade')->searchable()->sortable(),
                TextColumn::make('city')->label('Cidade')->searchable(),
                TextColumn::make('phone')->label('Telefone'),
                TextColumn::make('email')->label('E-mail'),
                TextColumn::make('nfse_provider_profile')->label('NFSe')->badge()->toggleable(),
                IconColumn::make('is_active')->label('Ativa')->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnits::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
