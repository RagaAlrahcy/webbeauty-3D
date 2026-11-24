<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ApplicationStatus;
use App\Enums\ProductStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\ResellerApplications\ResellerApplicationResource;
use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\ResellerApplication;
use App\Models\Subscriber;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/** Catalog and inbox numbers that need a decision or follow-up. */
class BusinessOverview extends StatsOverviewWidget
{
    /** Window for the "new in the last x days" descriptions and sparklines. */
    public const int RECENT_DAYS = 30;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return __('admin.dashboard.business');
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $published = Product::query()->published()->count();
        $drafts = Product::query()->where('status', ProductStatus::Draft)->count();
        $scheduled = Product::query()->where('status', ProductStatus::Published)->where('published_at', '>', now())->count();

        $unread = ContactMessage::query()->whereNull('read_at')->count();
        $recentMessages = self::recentCount(ContactMessage::query());

        $newApplications = ResellerApplication::query()->where('status', ApplicationStatus::New)->count();
        $applications = ResellerApplication::query()->count();

        $subscribers = Subscriber::query()->count();
        $recentSubscribers = self::recentCount(Subscriber::query());

        return [
            Stat::make(__('admin.dashboard.stats.products'), $published)
                ->description(__('admin.dashboard.stats.products_detail', ['drafts' => $drafts, 'scheduled' => $scheduled]))
                ->icon('heroicon-o-sparkles')
                ->url(ProductResource::getUrl()),

            Stat::make(__('admin.dashboard.stats.unread_messages'), $unread)
                ->description(__('admin.dashboard.stats.recent', ['count' => $recentMessages, 'days' => self::RECENT_DAYS]))
                ->icon('heroicon-o-envelope')
                ->color($unread > 0 ? 'warning' : 'gray')
                ->chart(self::dailyCounts(ContactMessage::query()))
                ->url(ContactMessageResource::getUrl()),

            Stat::make(__('admin.dashboard.stats.new_resellers'), $newApplications)
                ->description(__('admin.dashboard.stats.total', ['count' => $applications]))
                ->icon('heroicon-o-user-group')
                ->color($newApplications > 0 ? 'warning' : 'gray')
                ->url(ResellerApplicationResource::getUrl()),

            Stat::make(__('admin.dashboard.stats.subscribers'), $subscribers)
                ->description(__('admin.dashboard.stats.recent_new', ['count' => $recentSubscribers, 'days' => self::RECENT_DAYS]))
                ->descriptionIcon($recentSubscribers > 0 ? 'heroicon-m-arrow-trending-up' : null)
                ->icon('heroicon-o-at-symbol')
                ->color($recentSubscribers > 0 ? 'success' : 'gray')
                ->chart(self::dailyCounts(Subscriber::query()))
                ->url(SubscriberResource::getUrl()),
        ];
    }

    private static function recentCount(Builder $query): int
    {
        return $query->where('created_at', '>=', now()->subDays(self::RECENT_DAYS)->startOfDay())->count();
    }

    /**
     * Records per day for the sparkline, oldest first (14 days).
     *
     * @return list<int>
     */
    private static function dailyCounts(Builder $query): array
    {
        return array_values(InboxActivityChart::countsPerDay($query, 14));
    }
}
