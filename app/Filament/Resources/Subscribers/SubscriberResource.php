<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subscribers;

use App\Filament\Resources\Subscribers\Pages\ManageSubscribers;
use App\Models\Subscriber;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAtSymbol;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('admin.subscriber.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.subscriber.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.inbox');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->label(__('admin.subscriber.email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('locale')
                    ->label(__('admin.common.locale'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime('j M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
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
            'index' => ManageSubscribers::route('/'),
        ];
    }
}
