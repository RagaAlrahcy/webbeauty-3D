<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\ResellerApplications\ResellerApplicationResource;
use App\Models\ResellerApplication;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestResellerApplications extends TableWidget
{
    protected static ?int $sort = 7;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.dashboard.latest_resellers'))
            ->query(ResellerApplication::query()->latest()->limit(5))
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard.empty_resellers'))
            ->emptyStateIcon('heroicon-o-user-group')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.reseller.name'))
                    ->description(fn (ResellerApplication $record): string => $record->city.' · '.$record->business_type->getLabel()),
                TextColumn::make('status')
                    ->label(__('admin.reseller.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->since(),
            ])
            ->headerActions([
                Action::make('all')
                    ->label(__('admin.dashboard.view_all'))
                    ->link()
                    ->url(ResellerApplicationResource::getUrl()),
            ]);
    }
}
