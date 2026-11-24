<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Read-only inbox for the public contact form. */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('admin.contact_message.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.contact_message.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.inbox');
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = ContactMessage::query()->whereNull('read_at')->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label(__('admin.contact_message.name')),
            TextEntry::make('created_at')->label(__('admin.common.created_at'))->dateTime('j M Y H:i'),
            TextEntry::make('email')->label(__('admin.contact_message.email'))->placeholder('—')->copyable(),
            TextEntry::make('phone')->label(__('admin.contact_message.phone'))->placeholder('—')->copyable(),
            TextEntry::make('subject')->label(__('admin.contact_message.subject'))->placeholder('—')->columnSpanFull(),
            TextEntry::make('message')->label(__('admin.contact_message.message'))->columnSpanFull()->prose(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')
                    ->label(__('admin.contact_message.read'))
                    ->state(fn (ContactMessage $record): bool => $record->read_at !== null)
                    ->boolean(),
                TextColumn::make('name')
                    ->label(__('admin.contact_message.name'))
                    ->searchable()
                    ->description(fn (ContactMessage $record): string => $record->email ?? $record->phone ?? ''),
                TextColumn::make('message')
                    ->label(__('admin.contact_message.message'))
                    ->limit(80)
                    ->description(fn (ContactMessage $record): string => (string) $record->subject)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('unread')
                    ->label(__('admin.contact_message.unread'))
                    ->query(fn (Builder $query) => $query->whereNull('read_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markRead')
                    ->label(__('admin.contact_message.mark_read'))
                    ->icon('heroicon-o-check')
                    ->visible(fn (ContactMessage $record): bool => $record->read_at === null)
                    ->action(fn (ContactMessage $record) => $record->update(['read_at' => now()])),
                Action::make('replyWhatsapp')
                    ->label(__('admin.contact_message.reply_whatsapp'))
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->url(fn (ContactMessage $record): ?string => SiteSetting::whatsappLink($record->phone))
                    ->openUrlInNewTab()
                    ->visible(fn (ContactMessage $record): bool => filled($record->phone)),
                Action::make('replyEmail')
                    ->label(__('admin.contact_message.reply_email'))
                    ->icon('heroicon-o-envelope')
                    ->url(fn (ContactMessage $record): string => 'mailto:'.$record->email)
                    ->visible(fn (ContactMessage $record): bool => filled($record->email)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markRead')
                        ->label(__('admin.contact_message.mark_read'))
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => ContactMessage::query()
                            ->whereKey($records->modelKeys())
                            ->whereNull('read_at')
                            ->update(['read_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContactMessages::route('/'),
        ];
    }
}
