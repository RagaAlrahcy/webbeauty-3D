<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\AnimationPreset;
use App\Enums\ContainerShape;
use App\Enums\Material;
use App\Enums\ProductStatus;
use App\Enums\RenderMode;
use App\Filament\Support\Translatable;
use App\Models\ProductVariant;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public const string HEX_RULE = 'regex:/^#[0-9a-fA-F]{6}$/';

    public const string BACKGROUND_RULE = 'regex:/^(#[0-9a-fA-F]{6}|(linear|radial)-gradient\([#0-9a-zA-Z%,.\s()-]+\))$/';

    /** Max upload sizes in KB (AGENTS.md sections 8 and 14). */
    public const int MAX_IMAGE_KB = 5120;

    public const int MAX_MODEL_KB = 5120;

    /** @var list<string> */
    public const array IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('product')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    self::infoTab(),
                    self::photosTab(),
                    self::designTab(),
                    self::variantsTab(),
                    self::seoTab(),
                ]),
        ]);
    }

    private static function infoTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.info'))
            ->icon('heroicon-o-information-circle')
            ->schema([
                Translatable::make('name', __('admin.product.name'), fn (string $path) => TextInput::make($path)
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation) use ($path): void {
                        if ($operation === 'create' && str_ends_with($path, '.id')) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }), required: true),

                Grid::make(3)->schema([
                    TextInput::make('slug')
                        ->label(__('admin.product.slug'))
                        ->helperText(__('admin.product.slug_help'))
                        ->required()
                        ->maxLength(140)
                        ->alphaDash()
                        ->unique(ignoreRecord: true),

                    Select::make('category_id')
                        ->label(__('admin.product.category'))
                        ->relationship('category', 'slug')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name)
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('price')
                        ->label(__('admin.product.price'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1_000_000_000)
                        ->prefix('Rp'),
                ]),

                Translatable::make('short_description', __('admin.product.short_description'), fn (string $path) => Textarea::make($path)
                    ->rows(2)
                    ->maxLength(160), required: true),

                Translatable::make('description', __('admin.product.description'), fn (string $path) => RichEditor::make($path)
                    ->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3', 'bulletList', 'orderedList'], ['undo', 'redo']]), columns: 1),

                Translatable::make('ingredients', __('admin.product.ingredients'), fn (string $path) => TagsInput::make($path)
                    ->placeholder(__('admin.product.ingredients_placeholder'))
                    ->reorderable()),

                Grid::make(3)->schema([
                    Select::make('status')
                        ->label(__('admin.product.status'))
                        ->options(ProductStatus::class)
                        ->default(ProductStatus::Draft)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                            $status = $state instanceof ProductStatus ? $state : ProductStatus::tryFrom((string) $state);

                            if ($status === ProductStatus::Published && blank($get('published_at'))) {
                                $set('published_at', now()->toDateTimeString());
                            }
                        }),

                    DateTimePicker::make('published_at')
                        ->label(__('admin.product.published_at'))
                        ->helperText(__('admin.product.published_at_help'))
                        ->seconds(false),

                    Toggle::make('is_featured')
                        ->label(__('admin.product.is_featured'))
                        ->inline(false),
                ]),
            ]);
    }

    private static function photosTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.photos'))
            ->icon('heroicon-o-photo')
            ->schema([
                SpatieMediaLibraryFileUpload::make('gallery')
                    ->label(__('admin.product.gallery'))
                    ->helperText(__('admin.product.gallery_help'))
                    ->collection('gallery')
                    ->conversion('thumb')
                    ->multiple()
                    ->reorderable()
                    ->panelLayout('grid')
                    ->image()
                    ->imageEditor()
                    ->acceptedFileTypes(self::IMAGE_TYPES)
                    ->maxSize(self::MAX_IMAGE_KB)
                    ->maxFiles(12)
                    ->minFiles(1)
                    ->required(),

                SpatieMediaLibraryFileUpload::make('og_image')
                    ->label(__('admin.product.og_image'))
                    ->helperText(__('admin.product.og_image_help'))
                    ->collection('og_image')
                    ->image()
                    ->acceptedFileTypes(self::IMAGE_TYPES)
                    ->maxSize(self::MAX_IMAGE_KB),
            ]);
    }

    private static function designTab(): Tab
    {
        $isMode = fn (RenderMode $mode): \Closure => fn (Get $get): bool => self::renderMode($get) === $mode;

        return Tab::make(__('admin.product.tabs.design'))
            ->icon('heroicon-o-cube')
            ->schema([
                Select::make('render_mode')
                    ->label(__('admin.product.render_mode'))
                    ->helperText(__('admin.product.render_mode_help'))
                    ->options(RenderMode::class)
                    ->default(RenderMode::Procedural)
                    ->required()
                    ->live(),

                Section::make(__('admin.product.model_section'))
                    ->description(__('admin.product.model_help'))
                    ->visible($isMode(RenderMode::Model3d))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('model')
                            ->label(__('admin.product.model'))
                            ->collection('model')
                            ->rules(['extensions:glb'])
                            ->maxSize(self::MAX_MODEL_KB)
                            ->required($isMode(RenderMode::Model3d))
                            ->validationMessages(['extensions' => __('admin.product.model_invalid')]),
                    ]),

                Section::make(__('admin.product.procedural_section'))
                    ->visible($isMode(RenderMode::Procedural))
                    ->columns(2)
                    ->schema([
                        Select::make('container_shape')
                            ->label(__('admin.product.container_shape'))
                            ->options(ContainerShape::class)
                            ->required($isMode(RenderMode::Procedural)),

                        SpatieMediaLibraryFileUpload::make('label_texture')
                            ->label(__('admin.product.label_texture'))
                            ->helperText(__('admin.product.label_texture_help'))
                            ->collection('label_texture')
                            ->image()
                            ->acceptedFileTypes(self::IMAGE_TYPES)
                            ->maxSize(self::MAX_IMAGE_KB),
                    ]),

                Section::make(__('admin.product.appearance_section'))
                    ->hidden($isMode(RenderMode::Image))
                    ->columns(2)
                    ->schema([
                        ColorPicker::make('base_color')
                            ->label(__('admin.product.base_color'))
                            ->hex()
                            ->rules([self::HEX_RULE]),

                        Select::make('material')
                            ->label(__('admin.product.material'))
                            ->options(Material::class)
                            ->default(Material::Glossy),

                        Select::make('animation_preset')
                            ->label(__('admin.product.animation_preset'))
                            ->helperText(__('admin.product.animation_preset_help'))
                            ->options(AnimationPreset::class)
                            ->default(AnimationPreset::Rotate),

                        TextInput::make('background_theme')
                            ->label(__('admin.product.background_theme'))
                            ->helperText(__('admin.product.background_theme_help'))
                            ->maxLength(255)
                            ->rules([self::BACKGROUND_RULE]),

                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema(collect(['x' => 0, 'y' => 0.4, 'z' => 4])
                                ->map(fn (float|int $default, string $axis) => TextInput::make("camera_position.{$axis}")
                                    ->label(__('admin.product.camera').' '.strtoupper($axis))
                                    ->numeric()
                                    ->minValue(-20)
                                    ->maxValue(20)
                                    ->placeholder((string) $default))
                                ->values()
                                ->all()),
                    ]),
            ]);
    }

    private static function variantsTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.variants'))
            ->icon('heroicon-o-swatch')
            ->schema([
                Repeater::make('variants')
                    ->label(__('admin.product.variants'))
                    ->helperText(__('admin.product.variants_help'))
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->reorderable()
                    ->collapsible()
                    ->defaultItems(0)
                    ->addActionLabel(__('admin.product.add_variant'))
                    ->itemLabel(fn (array $state): ?string => $state['label']['id'] ?? null)
                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                        $variant = isset($data['id']) ? ProductVariant::query()->find($data['id']) : null;

                        return Translatable::fill($data, $variant);
                    })
                    ->columns(2)
                    ->schema([
                        Translatable::make('label', __('admin.product.variant_label'), fn (string $path) => TextInput::make($path)
                            ->maxLength(60), required: true),

                        ColorPicker::make('color')
                            ->label(__('admin.product.variant_color'))
                            ->hex()
                            ->required()
                            ->rules([self::HEX_RULE]),

                        SpatieMediaLibraryFileUpload::make('label_texture')
                            ->label(__('admin.product.label_texture'))
                            ->collection('label_texture')
                            ->image()
                            ->acceptedFileTypes(self::IMAGE_TYPES)
                            ->maxSize(self::MAX_IMAGE_KB),

                        SpatieMediaLibraryFileUpload::make('image')
                            ->label(__('admin.product.variant_image'))
                            ->collection('image')
                            ->image()
                            ->acceptedFileTypes(self::IMAGE_TYPES)
                            ->maxSize(self::MAX_IMAGE_KB),
                    ]),
            ]);
    }

    private static function seoTab(): Tab
    {
        return Tab::make(__('admin.product.tabs.seo'))
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                Translatable::make('meta_title', __('admin.product.meta_title'), fn (string $path) => TextInput::make($path)
                    ->maxLength(70)
                    ->placeholder(__('admin.product.meta_title_placeholder'))),

                Translatable::make('meta_description', __('admin.product.meta_description'), fn (string $path) => Textarea::make($path)
                    ->rows(2)
                    ->maxLength(160)
                    ->placeholder(__('admin.product.meta_description_placeholder'))),
            ]);
    }

    private static function renderMode(Get $get): ?RenderMode
    {
        $value = $get('render_mode');

        return $value instanceof RenderMode ? $value : RenderMode::tryFrom((string) $value);
    }
}
