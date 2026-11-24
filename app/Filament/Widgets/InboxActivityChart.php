<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\ResellerApplication;
use App\Models\Subscriber;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

/** Daily contact messages, reseller applications and newsletter signups. */
class InboxActivityChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    public function getHeading(): string
    {
        return __('admin.dashboard.inbox_activity');
    }

    public function getDescription(): string
    {
        return __('admin.dashboard.inbox_activity_help');
    }

    protected function getFilters(): array
    {
        return [
            '7' => __('admin.dashboard.last_days', ['days' => 7]),
            '30' => __('admin.dashboard.last_days', ['days' => 30]),
            '90' => __('admin.dashboard.last_days', ['days' => 90]),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = in_array($this->filter, ['7', '30', '90'], true) ? (int) $this->filter : 30;

        $series = [
            [__('admin.contact_message.plural'), ContactMessage::query(), '#e11d48'],
            [__('admin.reseller.plural'), ResellerApplication::query(), '#f59e0b'],
            [__('admin.subscriber.plural'), Subscriber::query(), '#10b981'],
        ];

        $labels = array_keys(self::emptyDays($days));

        return [
            'datasets' => array_map(fn (array $item): array => [
                'label' => $item[0],
                'data' => array_values(self::countsPerDay($item[1], $days)),
                'borderColor' => $item[2],
                'backgroundColor' => $item[2],
                'tension' => 0.3,
                'pointRadius' => $days > 30 ? 0 : 2,
            ], $series),
            'labels' => array_map(fn (string $day): string => Carbon::parse($day)->translatedFormat('j M'), $labels),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }

    /**
     * Number of records created on each of the last $days days (including today),
     * keyed by Y-m-d, oldest first; days without records are 0.
     *
     * @return array<string, int>
     */
    public static function countsPerDay(Builder $query, int $days): array
    {
        $counts = self::emptyDays($days);

        $totals = $query->clone()
            ->where('created_at', '>=', array_key_first($counts))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        foreach ($totals as $day => $total) {
            if (array_key_exists($day, $counts)) {
                $counts[$day] = (int) $total;
            }
        }

        return $counts;
    }

    /**
     * @return array<string, int> the last $days dates (Y-m-d) up to today, all 0
     */
    private static function emptyDays(int $days): array
    {
        $counts = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $counts[now()->subDays($offset)->toDateString()] = 0;
        }

        return $counts;
    }
}
