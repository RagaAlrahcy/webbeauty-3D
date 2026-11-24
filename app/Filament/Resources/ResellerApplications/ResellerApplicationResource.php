<?php

declare(strict_types=1);

namespace App\Filament\Resources\ResellerApplications;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\ResellerApplications\Pages\ManageResellerApplications;
use App\Models\ResellerApplication;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Reseller applications: the admin only changes the status and internal notes. */
class ResellerApplicationResource extends Resource
{
    protected static ?string $model = ResellerApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('admin.reseller.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.reseller.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.inbox');
    }

    public static function getNavigationBadge(): ?string
    {
        $new = ResellerApplication::query()->where('status', ApplicationStatus::New)->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('status')
                ->label(__('admin.reseller.status'))
                ->options(ApplicationStatus::class)
                ->required(),
            Textarea::make('notes')
                ->label(__('admin.reseller.notes'))
                ->rows(4)
                ->maxLength(2000),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label(__('admin.reseller.name')),
            TextEntry::make('status')->label(__('admin.reseller.status'))->badge(),
            TextEntry::make('phone')->label(__('admin.reseller.phone'))->copyable(),
            TextEntry::make('email')->label(__('admin.reseller.email'))->placeholder('—')->copyable(),
            TextEntry::make('city')->label(__('admin.reseller.city')),
            TextEntry::make('business_type')->label(__('admin.reseller.business_type')),
            TextEntry::make('shop_url')->label(__('admin.reseller.shop_url'))->placeholder('—')
                ->url(fn (ResellerApplication $record): ?string => $record->shop_url)
                ->openUrlInNewTab()
                ->columnSpanFull(),
            TextEntry::make('message')->label(__('admin.reseller.message'))->placeholder('—')->columnSpanFull(),
            TextEntry::make('notes')->label(__('admin.reseller.notes'))->placeholder('—')->columnSpanFull(),
            TextEntry::make('created_at')->label(__('admin.common.created_at'))->dateTime('j M Y H:i'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.reseller.name'))
                    ->searchable()
                    ->description(fn (ResellerApplication $record): string => $record->phone),
                TextColumn::make('city')
                    ->label(__('admin.reseller.city'))
                    ->searchable(),
                TextColumn::make('business_type')
                    ->label(__('admin.reseller.business_type'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label(__('admin.reseller.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.reseller.status'))
                    ->options(ApplicationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->url(fn (ResellerApplication $record): ?string => SiteSetting::whatsappLink($record->phone))
                    ->openUrlInNewTab(),
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
            'index' => ManageResellerApplications::route('/'),
        ];
    }
}
