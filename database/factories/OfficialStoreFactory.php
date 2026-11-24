<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MarketplacePlatform;
use App\Enums\StoreType;
use App\Models\OfficialStore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialStore>
 */
class OfficialStoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company().' Official Store',
            'type' => StoreType::Marketplace,
            'platform' => MarketplacePlatform::Shopee,
            'url' => 'https://shopee.co.id/'.$this->faker->slug(2),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function offline(): static
    {
        return $this->state([
            'type' => StoreType::Offline,
            'platform' => null,
            'url' => null,
            'city' => 'Bandung',
            'address' => 'Jl. Braga No. 1',
        ]);
    }
}
