<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestContactMessages extends TableWidget
{
    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.dashboard.latest_messages'))
            ->query(ContactMessage::query()->latest()->limit(5))
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard.empty_inbox'))
            ->emptyStateIcon('heroicon-o-envelope-open')
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->state(fn (ContactMessage $record): bool => $record->read_at !== null)
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-s-envelope')
                    ->falseColor('warning'),
                TextColumn::make('name')
                    ->label(__('admin.contact_message.name'))
                    ->description(fn (ContactMessage $record): string => str($record->message)->limit(60)->toString())
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->since(),
            ])
            ->headerActions([
                Action::make('all')
                    ->label(__('admin.dashboard.view_all'))
                    ->link()
                    ->url(ContactMessageResource::getUrl()),
            ]);
    }
}
