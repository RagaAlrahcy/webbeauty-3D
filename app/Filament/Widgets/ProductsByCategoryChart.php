<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

/** Published products per category, to spot thin categories. */
class ProductsByCategoryChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    public function getHeading(): string
    {
        return __('admin.dashboard.products_by_category');
    }

    public function getDescription(): string
    {
        return __('admin.dashboard.products_by_category_help');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $categories = Category::query()
            ->withCount(['products' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->get();

        return [
            'datasets' => [[
                'label' => __('admin.dashboard.stats.products'),
                'data' => $categories->pluck('products_count')->all(),
                'backgroundColor' => '#fb7185',
                'borderRadius' => 6,
            ]],
            'labels' => $categories->map(fn (Category $category): string => $category->name)->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}
