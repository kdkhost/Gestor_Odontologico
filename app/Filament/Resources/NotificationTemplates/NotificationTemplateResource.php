<?php

namespace App\Filament\Resources\NotificationTemplates;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\NotificationTemplates\Pages\ManageNotificationTemplates;
use App\Models\NotificationTemplate;
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
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class NotificationTemplateResource extends ClinicResource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string $permissionPrefix = 'notificacoes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Notificações';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')
                ->label('Unidade')
                ->options(Unit::query()->pluck('name', 'id'))
                ->searchable()
                ->preload(),
            TextInput::make('name')->label('Nome')->required()->maxLength(120),
            Select::make('channel')
                ->label('Canal')
                ->options([
                    'whatsapp' => 'WhatsApp',
                    'push' => 'Push',
                    'email' => 'E-mail',
                ])
                ->default('whatsapp')
                ->required(),
            Select::make('provider')
                ->label('Provedor')
                ->options([
                    'evolution' => 'Evolution API',
                    'internal' => 'Interno / manual',
                ])
                ->default('evolution')
                ->required(),
            TextInput::make('trigger_event')->label('Evento gatilho')->required()->maxLength(50),
            TextInput::make('subject')->label('Assunto')->maxLength(120),
            KeyValue::make('variables')->label('Variáveis disponíveis')->columnSpanFull(),
            Textarea::make('message')
                ->label('Mensagem')
                ->rows(10)
                ->required()
                ->helperText('Use placeholders como {{paciente_nome}} e formatação nativa do WhatsApp, por exemplo *negrito*, _itálico_ e listas em texto.')
                ->columnSpanFull(),
            TextInput::make('delivery_window_start')->label('Janela inicial')->placeholder('08:00'),
            TextInput::make('delivery_window_end')->label('Janela final')->placeholder('18:00'),
            TextInput::make('cooldown_seconds')->label('Intervalo mínimo entre envios (s)')->numeric()->default(120)->required(),
            TextInput::make('hourly_limit_per_recipient')->label('Máximo por hora para o mesmo destinatário')->numeric()->default(4)->required(),
            Toggle::make('requires_opt_in')->label('Exigir opt-in confirmado')->default(true),
            Toggle::make('requires_official_window')->label('Restringir à janela oficial configurada')->default(true),
            Toggle::make('is_active')->label('Ativo')->default(true),
            KeyValue::make('meta')->label('Metadados e exemplos')->columnSpanFull(),
            Placeholder::make('whatsapp_guidelines')
                ->label('Boas práticas')
                ->content('Evite campanhas contínuas, mensagens fora da janela permitida, textos excessivos e envios sem opt-in. O serviço aplica bloqueios por intervalo, por minuto e por destinatário.')
                ->columnSpanFull(),
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
                TextColumn::make('name')->label('Template')->searchable()->sortable(),
                TextColumn::make('channel')->label('Canal')->badge(),
                TextColumn::make('provider')->label('Provedor')->badge(),
                TextColumn::make('trigger_event')->label('Evento')->searchable(),
                TextColumn::make('cooldown_seconds')->label('Cooldown (s)'),
                TextColumn::make('hourly_limit_per_recipient')->label('Máx/hora'),
                IconColumn::make('requires_opt_in')->label('Opt-in')->boolean(),
                IconColumn::make('requires_official_window')->label('Janela')->boolean(),
                IconColumn::make('is_active')->label('Ativo')->boolean(),
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
            'index' => ManageNotificationTemplates::route('/'),
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
