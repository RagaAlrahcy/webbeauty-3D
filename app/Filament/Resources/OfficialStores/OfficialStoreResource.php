<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfficialStores;

use App\Enums\MarketplacePlatform;
use App\Enums\StoreType;
use App\Filament\Resources\OfficialStores\Pages\CreateOfficialStore;
use App\Filament\Resources\OfficialStores\Pages\EditOfficialStore;
use App\Filament\Resources\OfficialStores\Pages\ListOfficialStores;
use App\Models\OfficialStore;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Official shops for the "where to buy" page (helps customers avoid counterfeits). */
class OfficialStoreResource extends Resource
{
    protected static ?string $model = OfficialStore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return __('admin.store.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.store.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function form(Schema $schema): Schema
    {
        $isType = fn (StoreType $type): \Closure => fn (Get $get): bool => self::type($get) === $type;

        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('admin.store.name'))
                        ->required()
                        ->maxLength(120),

                    Select::make('type')
                        ->label(__('admin.store.type'))
                        ->options(StoreType::class)
                        ->default(StoreType::Marketplace)
                        ->required()
                        ->live(),

                    Select::make('platform')
                        ->label(__('admin.store.platform'))
                        ->options(MarketplacePlatform::class)
                        ->visible($isType(StoreType::Marketplace)),

                    TextInput::make('url')
                        ->label(__('admin.store.url'))
                        ->url()
                        ->maxLength(255)
                        ->required($isType(StoreType::Marketplace)),

                    TextInput::make('city')
                        ->label(__('admin.store.city'))
                        ->maxLength(80)
                        ->visible($isType(StoreType::Offline)),

                    TextInput::make('phone')
                        ->label(__('admin.store.phone'))
                        ->tel()
                        ->maxLength(30)
                        ->visible($isType(StoreType::Offline)),

                    Textarea::make('address')
                        ->label(__('admin.store.address'))
                        ->rows(2)
                        ->maxLength(500)
                        ->visible($isType(StoreType::Offline)),

                    TextInput::make('maps_url')
                        ->label(__('admin.store.maps_url'))
                        ->url()
                        ->maxLength(255)
                        ->visible($isType(StoreType::Offline)),

                    SpatieMediaLibraryFileUpload::make('logo')
                        ->label(__('admin.store.logo'))
                        ->collection('logo')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/webp', 'image/jpeg', 'image/svg+xml'])
                        ->maxSize(1024),

                    Toggle::make('is_published')
                        ->label(__('admin.common.is_published'))
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('media'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->label(__('admin.store.logo'))
                    ->collection('logo')
                    ->conversion('thumb'),
                TextColumn::make('name')
                    ->label(__('admin.store.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.store.type'))
                    ->badge(),
                TextColumn::make('platform')
                    ->label(__('admin.store.platform'))
                    ->placeholder('—'),
                TextColumn::make('city')
                    ->label(__('admin.store.city'))
                    ->placeholder('—'),
                IconColumn::make('is_published')
                    ->label(__('admin.common.is_published'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.store.type'))
                    ->options(StoreType::class),
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ListOfficialStores::route('/'),
            'create' => CreateOfficialStore::route('/create'),
            'edit' => EditOfficialStore::route('/{record}/edit'),
        ];
    }

    private static function type(Get $get): ?StoreType
    {
        $value = $get('type');

        return $value instanceof StoreType ? $value : StoreType::tryFrom((string) $value);
    }
}
