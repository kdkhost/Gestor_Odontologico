<?php

namespace App\Filament\Resources\DocumentTemplates;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\DocumentTemplates\Pages\ManageDocumentTemplates;
use App\Models\DocumentTemplate;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentTemplateResource extends ClinicResource
{
    protected static ?string $model = DocumentTemplate::class;

    protected static string $permissionPrefix = 'documentos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Documentos';

    protected static string|\UnitEnum|null $navigationGroup = 'Clínico';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('name')->label('Nome')->required(),
            TextInput::make('slug')->label('Slug')->required(),
            Select::make('category')->label('Categoria')->options([
                'consentimento' => 'Consentimento',
                'contrato' => 'Contrato',
                'atestado' => 'Atestado',
                'outro' => 'Outro',
            ])->required(),
            Select::make('channel')->label('Canal')->options([
                'portal' => 'Portal',
                'admin' => 'Administrativo',
            ])->required(),
            TextInput::make('subject')->label('Assunto'),
            KeyValue::make('variables')->label('Variáveis'),
            RichEditor::make('body')->label('Conteúdo')->required()->columnSpanFull(),
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
            TextColumn::make('name')->label('Documento')->searchable()->sortable(),
            TextColumn::make('category')->label('Categoria')->badge(),
            TextColumn::make('channel')->label('Canal'),
            TextColumn::make('unit.name')->label('Unidade'),
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
            'index' => ManageDocumentTemplates::route('/'),
        ];
    }
}
