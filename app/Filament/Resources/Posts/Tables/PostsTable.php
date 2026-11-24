<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\ProductStatus;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('media'))
            ->defaultSort('published_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->label(__('admin.post.cover'))
                    ->collection('cover')
                    ->conversion('card'),

                TextColumn::make('title')
                    ->label(__('admin.post.title'))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('title->id', 'like', "%{$search}%")
                        ->orWhere('title->en', 'like', "%{$search}%"))
                    ->description(fn (Post $record): string => $record->slug)
                    ->wrap(),

                TextColumn::make('status')
                    ->label(__('admin.product.status'))
                    ->badge(),

                TextColumn::make('published_at')
                    ->label(__('admin.product.published_at'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.product.status'))
                    ->options(ProductStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('admin.product.view_on_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Post $record): string => route('posts.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (Post $record): bool => $record->status === ProductStatus::Published
                        && $record->published_at?->isPast() === true),
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
