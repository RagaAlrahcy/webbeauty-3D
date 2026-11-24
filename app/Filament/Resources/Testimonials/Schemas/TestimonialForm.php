<?php

declare(strict_types=1);

namespace App\Filament\Resources\Testimonials\Schemas;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Support\Translatable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('customer_name')
                        ->label(__('admin.testimonial.customer_name'))
                        ->required()
                        ->maxLength(80),

                    Select::make('product_id')
                        ->label(__('admin.common.product'))
                        ->helperText(__('admin.testimonial.product_help'))
                        ->relationship('product', 'slug')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name)
                        ->searchable()
                        ->preload(),

                    Translatable::make('customer_info', __('admin.testimonial.customer_info'), fn (string $path) => TextInput::make($path)
                        ->maxLength(80)
                        ->placeholder(__('admin.testimonial.customer_info_placeholder'))),

                    Translatable::make('content', __('admin.testimonial.content'), fn (string $path) => Textarea::make($path)
                        ->rows(4)
                        ->maxLength(600), required: true),

                    Grid::make(3)->columnSpanFull()->schema([
                        Select::make('rating')
                            ->label(__('admin.testimonial.rating'))
                            ->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★']),

                        Toggle::make('is_featured')
                            ->label(__('admin.testimonial.is_featured'))
                            ->inline(false),

                        Toggle::make('is_published')
                            ->label(__('admin.common.is_published'))
                            ->default(true)
                            ->inline(false),
                    ]),
                ]),

            Section::make()
                ->columnSpanFull()
                ->columns(3)
                ->description(__('admin.testimonial.before_after_help'))
                ->schema([
                    self::image('photo', __('admin.testimonial.photo')),
                    self::image('before', __('admin.testimonial.before')),
                    self::image('after', __('admin.testimonial.after')),
                ]),
        ]);
    }

    private static function image(string $collection, string $label): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($collection)
            ->label($label)
            ->collection($collection)
            ->image()
            ->imageEditor()
            ->acceptedFileTypes(ProductForm::IMAGE_TYPES)
            ->maxSize(ProductForm::MAX_IMAGE_KB);
    }
}
