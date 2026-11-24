<?php

declare(strict_types=1);

namespace App\Filament\Resources\Promotions;

use App\Enums\PromotionPlacement;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Support\Translatable;
use App\Models\Promotion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PromotionResource extends Resource
{
    /** Internal path (/produk) or absolute http(s) URL; same rule as the homepage CTA. */
    public const string LINK_RULE = 'regex:/^(\/|https?:\/\/)\S*$/';

    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('admin.promotion.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.promotion.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.content');
    }

    public static function form(Schema $schema): Schema
    {
        $isHomeBanner = fn (Get $get): bool => self::placement($get) === PromotionPlacement::Home;

        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Select::make('placement')
                        ->label(__('admin.promotion.placement'))
                        ->options(PromotionPlacement::class)
                        ->default(PromotionPlacement::TopBar)
                        ->required()
                        ->live(),

                    Translatable::make('title', __('admin.promotion.title'), fn (string $path) => TextInput::make($path)
                        ->maxLength(120), required: true),

                    Translatable::make('body', __('admin.promotion.body'), fn (string $path) => Textarea::make($path)
                        ->rows(2)
                        ->maxLength(240)),

                    Grid::make(2)->columnSpanFull()->schema([
                        TextInput::make('link_url')
                            ->label(__('admin.promotion.link_url'))
                            ->helperText(__('admin.homepage.cta_url_help'))
                            ->maxLength(255)
                            ->rules([self::LINK_RULE]),
                    ]),

                    Translatable::make('link_label', __('admin.promotion.link_label'), fn (string $path) => TextInput::make($path)
                        ->maxLength(40)),

                    SpatieMediaLibraryFileUpload::make('image')
                        ->label(__('admin.promotion.image'))
                        ->helperText(__('admin.promotion.image_help'))
                        ->collection('image')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(ProductForm::IMAGE_TYPES)
                        ->maxSize(ProductForm::MAX_IMAGE_KB)
                        ->visible($isHomeBanner),
                ]),

            Section::make()
                ->columnSpanFull()
                ->columns(3)
                ->description(__('admin.promotion.schedule_help'))
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label(__('admin.promotion.starts_at'))
                        ->seconds(false),

                    DateTimePicker::make('ends_at')
                        ->label(__('admin.promotion.ends_at'))
                        ->seconds(false)
                        ->afterOrEqual('starts_at'),

                    Toggle::make('is_active')
                        ->label(__('admin.promotion.is_active'))
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.promotion.title'))
                    ->wrap(),
                TextColumn::make('placement')
                    ->label(__('admin.promotion.placement'))
                    ->badge(),
                TextColumn::make('starts_at')
                    ->label(__('admin.promotion.starts_at'))
                    ->dateTime('j M Y H:i')
                    ->placeholder('—'),
                TextColumn::make('ends_at')
                    ->label(__('admin.promotion.ends_at'))
                    ->dateTime('j M Y H:i')
                    ->placeholder('—'),
                IconColumn::make('running')
                    ->label(__('admin.promotion.running'))
                    ->state(fn (Promotion $record): bool => $record->isRunning())
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('placement')
                    ->label(__('admin.promotion.placement'))
                    ->options(PromotionPlacement::class),
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
            'index' => ListPromotions::route('/'),
            'create' => CreatePromotion::route('/create'),
            'edit' => EditPromotion::route('/{record}/edit'),
        ];
    }

    private static function placement(Get $get): ?PromotionPlacement
    {
        $value = $get('placement');

        return $value instanceof PromotionPlacement ? $value : PromotionPlacement::tryFrom((string) $value);
    }
}
