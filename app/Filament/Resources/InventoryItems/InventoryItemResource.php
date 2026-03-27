<?php

namespace App\Filament\Resources\InventoryItems;

use App\Filament\Resources\ClinicResource;
use App\Filament\Resources\InventoryItems\Pages\ManageInventoryItems;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryItemResource extends ClinicResource
{
    protected static ?string $model = InventoryItem::class;

    protected static string $permissionPrefix = 'estoque';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Itens de estoque';

    protected static string|\UnitEnum|null $navigationGroup = 'Estoque';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unidade')->options(Unit::query()->pluck('name', 'id'))->required()->searchable()->preload(),
            Select::make('inventory_category_id')->label('Categoria')->options(InventoryCategory::query()->pluck('name', 'id'))->searchable()->preload(),
            Select::make('supplier_id')->label('Fornecedor')->options(Supplier::query()->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('name')->label('Item')->required(),
            TextInput::make('sku')->label('SKU'),
            TextInput::make('barcode')->label('Código de barras'),
            TextInput::make('unit_measure')->label('Unidade de medida')->default('un'),
            TextInput::make('minimum_stock')->label('Estoque mínimo')->numeric()->default(0),
            TextInput::make('current_stock')->label('Estoque atual')->numeric()->default(0),
            TextInput::make('cost_price')->label('Custo')->numeric()->prefix('R$')->default(0),
            TextInput::make('sale_price')->label('Preço de venda')->numeric()->prefix('R$')->default(0),
            Toggle::make('requires_batch')->label('Controla lote')->default(false),
            Toggle::make('is_active')->label('Ativo')->default(true),
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
            TextColumn::make('name')->label('Item')->searchable()->sortable(),
            TextColumn::make('unit.name')->label('Unidade'),
            TextColumn::make('current_stock')->label('Atual'),
            TextColumn::make('minimum_stock')->label('Mínimo'),
            TextColumn::make('cost_price')->label('Custo'),
            IconColumn::make('requires_batch')->label('Lote')->boolean(),
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
            'index' => ManageInventoryItems::route('/'),
        ];
    }
}
