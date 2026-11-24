<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\BusinessOverview;
use App\Filament\Widgets\ContentOverview;
use App\Filament\Widgets\InboxActivityChart;
use App\Filament\Widgets\LatestContactMessages;
use App\Filament\Widgets\LatestResellerApplications;
use App\Filament\Widgets\ProductsByCategoryChart;
use App\Filament\Widgets\SiteHealthChecklist;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

/** Admin home: business summary, content checklist and the latest inbox items. */
class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return __('admin.dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.dashboard.title');
    }

    public function getSubheading(): string
    {
        return __('admin.dashboard.subheading', ['date' => now()->translatedFormat('l, j F Y')]);
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }

    public function getWidgets(): array
    {
        return [
            BusinessOverview::class,
            SiteHealthChecklist::class,
            ContentOverview::class,
            InboxActivityChart::class,
            ProductsByCategoryChart::class,
            LatestContactMessages::class,
            LatestResellerApplications::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewSite')
                ->label(__('admin.dashboard.view_site'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(route('home'))
                ->openUrlInNewTab(),
        ];
    }
}
