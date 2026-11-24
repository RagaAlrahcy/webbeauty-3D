<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\SiteHealth;
use Filament\Widgets\Widget;

/** "Needs attention" list built from App\Services\SiteHealth. */
class SiteHealthChecklist extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.site-health-checklist';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'issues' => app(SiteHealth::class)->issues(),
        ];
    }
}
