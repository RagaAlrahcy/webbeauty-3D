<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Support\Translatable;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Translatable::make('title', __('admin.post.title'), fn (string $path) => TextInput::make($path)
                        ->maxLength(140)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state, string $operation) use ($path): void {
                            if ($operation === 'create' && str_ends_with($path, '.id')) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }), required: true),

                    TextInput::make('slug')
                        ->label(__('admin.product.slug'))
                        ->required()
                        ->maxLength(160)
                        ->alphaDash()
                        ->unique(ignoreRecord: true),

                    Translatable::make('excerpt', __('admin.post.excerpt'), fn (string $path) => Textarea::make($path)
                        ->rows(2)
                        ->maxLength(300), required: true),

                    Translatable::make('content', __('admin.post.content'), fn (string $path) => RichEditor::make($path)
                        ->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3', 'bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]), columns: 1),
                ]),

            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    SpatieMediaLibraryFileUpload::make('cover')
                        ->label(__('admin.post.cover'))
                        ->collection('cover')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(ProductForm::IMAGE_TYPES)
                        ->maxSize(ProductForm::MAX_IMAGE_KB),

                    Select::make('products')
                        ->label(__('admin.post.products'))
                        ->helperText(__('admin.post.products_help'))
                        ->relationship('products', 'slug')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name)
                        ->multiple()
                        ->searchable()
                        ->preload(),

                    Grid::make(2)->columnSpanFull()->schema([
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
                            ->seconds(false),
                    ]),
                ]),

            Section::make(__('admin.product.tabs.seo'))
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    Translatable::make('meta_title', __('admin.product.meta_title'), fn (string $path) => TextInput::make($path)
                        ->maxLength(70)),

                    Translatable::make('meta_description', __('admin.product.meta_description'), fn (string $path) => Textarea::make($path)
                        ->rows(2)
                        ->maxLength(160)),
                ]),
        ]);
    }
}
