<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PromotionPlacement;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'placement' => PromotionPlacement::TopBar,
            'title' => ['id' => 'Gratis ongkir', 'en' => 'Free shipping'],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function home(): static
    {
        return $this->state(['placement' => PromotionPlacement::Home]);
    }
}
