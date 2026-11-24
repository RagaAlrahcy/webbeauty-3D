<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RenderMode: string implements HasLabel
{
    case Model3d = 'model3d';
    case Procedural = 'procedural';
    case Image = 'image';

    public function getLabel(): string
    {
        return match ($this) {
            self::Model3d => __('admin.render_mode.model3d'),
            self::Procedural => __('admin.render_mode.procedural'),
            self::Image => __('admin.render_mode.image'),
        };
    }
}
