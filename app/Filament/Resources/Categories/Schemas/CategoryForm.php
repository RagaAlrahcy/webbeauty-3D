<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\Translatable;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Translatable::make('name', __('admin.category.name'), fn (string $path) => TextInput::make($path)
                        ->maxLength(80)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state, string $operation) use ($path): void {
                            if ($operation === 'create' && str_ends_with($path, '.id')) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }), required: true),

                    TextInput::make('slug')
                        ->label(__('admin.product.slug'))
                        ->required()
                        ->maxLength(100)
                        ->alphaDash()
                        ->unique(ignoreRecord: true),

                    TextInput::make('sort_order')
                        ->label(__('admin.category.sort_order'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0),

                    SpatieMediaLibraryFileUpload::make('icon')
                        ->label(__('admin.category.icon'))
                        ->collection('icon')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/webp', 'image/svg+xml'])
                        ->maxSize(1024),
                ]),
        ]);
    }
}
