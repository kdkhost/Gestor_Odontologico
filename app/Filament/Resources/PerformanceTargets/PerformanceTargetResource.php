<?php

namespace App\Filament\Resources\PerformanceTargets;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\PerformanceTargets\Pages\ManagePerformanceTargets;
use App\Models\PerformanceTarget;
use App\Models\Professional;
use App\Models\Unit;
use App\Services\BusinessIntelligenceService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PerformanceTargetResource extends ClinicResource
{
    protected static ?string $model = PerformanceTarget::class;

    protected static string $permissionPrefix = 'financeiro';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Metas';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')
                ->label('Unidade')
                ->options(Unit::query()->orderBy('name')->pluck('name', 'id'))
                ->helperText('Deixe em branco para meta geral. Se houver profissional, a leitura será individual.')
                ->searchable()
                ->preload(),
            Select::make('professional_id')
                ->label('Profissional')
                ->options(Professional::query()->with('user')->get()->mapWithKeys(fn (Professional $professional) => [
                    $professional->id => $professional->user?->name ?? "Profissional #{$professional->id}",
                ]))
                ->helperText('Use para meta específica de um profissional.')
                ->searchable()
                ->preload(),
            Select::make('metric')
                ->label('Métrica')
                ->options(app(BusinessIntelligenceService::class)->metricOptions())
                ->required(),
            DatePicker::make('period_start')->label('Início')->required(),
            DatePicker::make('period_end')->label('Fim')->required(),
            TextInput::make('target_value')->label('Meta')->numeric()->required(),
            Toggle::make('is_active')->label('Ativa')->default(true),
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
            TextColumn::make('professional_id')
                ->label('Escopo')
                ->formatStateUsing(fn ($state, PerformanceTarget $record): string => $record->professional_id
                    ? 'Profissional'
                    : ($record->unit_id ? 'Unidade' : 'Geral')),
            TextColumn::make('metric')
                ->label('Métrica')
                ->formatStateUsing(fn (string $state): string => app(BusinessIntelligenceService::class)->metricOptions()[$state] ?? $state),
            TextColumn::make('unit.name')->label('Unidade')->placeholder('Todas'),
            TextColumn::make('professional.user.name')->label('Profissional')->placeholder('Escopo geral'),
            TextColumn::make('period_start')->label('Início')->date('d/m/Y'),
            TextColumn::make('period_end')->label('Fim')->date('d/m/Y'),
            TextColumn::make('target_value')->label('Meta')->numeric(decimalPlaces: 2),
            IconColumn::make('is_active')->label('Ativa')->boolean(),
        ])->recordActions([
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
            'index' => ManagePerformanceTargets::route('/'),
        ];
    }
}
