<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AnimationPreset: string implements HasLabel
{
    case Rotate = 'rotate';
    case Float = 'float';
    case ScrollReveal = 'scroll_reveal';
    case Explode = 'explode';
    case None = 'none';

    public function getLabel(): string
    {
        return __('admin.animation_preset.'.$this->value);
    }

    /** Explode needs named nodes, which only uploaded GLB models have. */
    public function requiresModel(): bool
    {
        return $this === self::Explode;
    }
}
