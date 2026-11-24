<?php

declare(strict_types=1);

namespace App\Filament\Resources\Testimonials\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'media']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->label(__('admin.testimonial.photo'))
                    ->collection('photo')
                    ->conversion('thumb')
                    ->circular(),

                TextColumn::make('customer_name')
                    ->label(__('admin.testimonial.customer_name'))
                    ->searchable()
                    ->description(fn ($record): string => str($record->content)->limit(80)->toString())
                    ->wrap(),

                TextColumn::make('product.name')
                    ->label(__('admin.common.product'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('rating')
                    ->label(__('admin.testimonial.rating'))
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) : '—'),

                IconColumn::make('is_featured')
                    ->label(__('admin.testimonial.is_featured'))
                    ->boolean(),

                IconColumn::make('is_published')
                    ->label(__('admin.common.is_published'))
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_featured')->label(__('admin.testimonial.is_featured')),
                TernaryFilter::make('is_published')->label(__('admin.common.is_published')),
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
}
