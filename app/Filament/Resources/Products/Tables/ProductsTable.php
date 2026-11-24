<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Enums\RenderMode;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'media']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('gallery')
                    ->label(__('admin.product.photo'))
                    ->collection('gallery')
                    ->conversion('thumb')
                    ->limit(1)
                    ->square(),

                TextColumn::make('name')
                    ->label(__('admin.product.name'))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('name->id', 'like', "%{$search}%")
                        ->orWhere('name->en', 'like', "%{$search}%"))
                    ->description(fn (Product $record): string => $record->slug)
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label(__('admin.product.category'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('price')
                    ->label(__('admin.product.price'))
                    ->formatStateUsing(fn (Product $record): ?string => $record->formattedPrice())
                    ->sortable(),

                TextColumn::make('render_mode')
                    ->label(__('admin.product.render_mode'))
                    ->badge(),

                TextColumn::make('status')
                    ->label(__('admin.product.status'))
                    ->badge(),

                IconColumn::make('is_featured')
                    ->label(__('admin.product.is_featured'))
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.product.status'))
                    ->options(ProductStatus::class),
                SelectFilter::make('render_mode')
                    ->label(__('admin.product.render_mode'))
                    ->options(RenderMode::class),
                SelectFilter::make('category')
                    ->label(__('admin.product.category'))
                    ->relationship('category', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('admin.product.view_on_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Product $record): string => route('products.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (Product $record): bool => $record->isPublished()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
