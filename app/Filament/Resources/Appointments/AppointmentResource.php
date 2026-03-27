<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\ManageAppointments;
use App\Filament\Resources\ClinicResource;
use App\Models\Appointment;
use App\Models\Chair;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Professional;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentResource extends ClinicResource
{
    protected static ?string $model = Appointment::class;

    protected static string $permissionPrefix = 'agenda';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Agenda';

    protected static string|\UnitEnum|null $navigationGroup = 'Operação';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->required()->searchable()->preload(),
            Select::make('patient_id')->label('Paciente')->options(Patient::query()->pluck('name', 'id'))->required()->searchable()->preload(),
            Select::make('professional_id')->label('Profissional')->options(Professional::query()->with('user')->get()->pluck('user.name', 'id'))->searchable()->preload(),
            Select::make('chair_id')->label('Cadeira / sala')->options(Chair::query()->pluck('name', 'id'))->searchable()->preload(),
            Select::make('procedure_id')->label('Procedimento')->options(Procedure::query()->pluck('name', 'id'))->searchable()->preload(),
            Select::make('status')->label('Status')->options(array_combine(config('clinic.appointment_statuses'), config('clinic.appointment_statuses')))->required(),
            Select::make('origin')->label('Origem')->options([
                'portal' => 'Portal',
                'admin' => 'Administrativo',
                'phone' => 'Telefone',
                'whatsapp' => 'WhatsApp',
            ])->required(),
            DateTimePicker::make('scheduled_start')->label('Início')->required(),
            DateTimePicker::make('scheduled_end')->label('Fim')->required(),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('scheduled_start')->label('Início')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('patient.name')->label('Paciente')->searchable(),
            TextColumn::make('professional.user.name')->label('Profissional'),
            TextColumn::make('procedure.name')->label('Procedimento'),
            TextColumn::make('unit.name')->label('Unidade'),
            TextColumn::make('status')->badge(),
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
            'index' => ManageAppointments::route('/'),
        ];
    }
}
