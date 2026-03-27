<?php

namespace App\Filament\Resources\Patients;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\Patients\Pages\ManagePatients;
use App\Filament\Resources\Patients\Pages\ViewPatientProfile;
use App\Models\Patient;
use App\Models\Patient as PatientRecord;
use App\Models\Unit;
use App\Services\PatientInsightService;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends ClinicResource
{
    protected static ?string $model = Patient::class;

    protected static string $permissionPrefix = 'pacientes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Pacientes';

    protected static string|\UnitEnum|null $navigationGroup = 'Cadastros';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->searchable()->preload(),
                TextInput::make('name')->label('Nome completo')->required(),
                TextInput::make('preferred_name')->label('Nome preferido'),
                TextInput::make('cpf')->label('CPF')->mask('999.999.999-99'),
                TextInput::make('email')->email(),
                TextInput::make('phone')->label('Celular')->mask('(99) 99999-9999'),
                TextInput::make('whatsapp')->mask('(99) 99999-9999'),
                TextInput::make('occupation')->label('Profissão'),
                TextInput::make('emergency_contact_name')->label('Contato de emergência'),
                TextInput::make('emergency_contact_phone')->label('Telefone de emergência')->mask('(99) 99999-9999'),
                TextInput::make('zip_code')->label('CEP')->mask('99999-999'),
                TextInput::make('street')->label('Rua'),
                TextInput::make('number')->label('Número'),
                TextInput::make('complement')->label('Complemento'),
                TextInput::make('district')->label('Bairro'),
                TextInput::make('city')->label('Cidade'),
                TextInput::make('state')->label('UF')->maxLength(2),
                Toggle::make('whatsapp_opt_in')
                    ->label('Opt-in para WhatsApp operacional')
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                        if ($state && blank($get('whatsapp_opt_in_at'))) {
                            $set('whatsapp_opt_in_at', now());
                        }

                        if (! $state) {
                            $set('whatsapp_opt_in_at', null);
                        }
                    }),
                DateTimePicker::make('whatsapp_opt_in_at')
                    ->label('Opt-in registrado em')
                    ->seconds(false)
                    ->readOnly(),
            ]),
            Textarea::make('allergies')->label('Alergias'),
            Textarea::make('continuous_medication')->label('Medicações contínuas'),
            Textarea::make('observations')->label('Observações'),
            Toggle::make('is_active')->label('Ativo')->default(true),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('patient_profile')
                ->hiddenLabel()
                ->view('filament.infolists.patient-profile')
                ->state(fn (PatientRecord $record): array => app(PatientInsightService::class)->snapshot($record))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Paciente')->searchable()->sortable(),
            TextColumn::make('cpf')->label('CPF'),
            TextColumn::make('phone')->label('Celular'),
            TextColumn::make('unit.name')->label('Unidade'),
            TextColumn::make('last_visit_at')->label('Última visita')->dateTime('d/m/Y H:i'),
            IconColumn::make('is_active')->label('Ativo')->boolean(),
        ])->filters([
            TrashedFilter::make(),
        ])->recordActions([
            ViewAction::make()
                ->label('Perfil 360')
                ->icon('heroicon-o-identification')
                ->url(fn (PatientRecord $record): string => static::getUrl('view', ['record' => $record])),
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ])->toolbarActions([
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
            'index' => ManagePatients::route('/'),
            'view' => ViewPatientProfile::route('/{record}/perfil-360'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
