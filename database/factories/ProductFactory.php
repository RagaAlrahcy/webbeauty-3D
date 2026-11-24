<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnimationPreset;
use App\Enums\ContainerShape;
use App\Enums\Material;
use App\Enums\ProductStatus;
use App\Enums\RenderMode;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => ['id' => $name, 'en' => $name.' EN'],
            'slug' => Str::slug($name),
            'short_description' => ['id' => 'Deskripsi singkat '.$name, 'en' => 'Short description '.$name],
            'price' => 99000,
            'render_mode' => RenderMode::Procedural,
            'container_shape' => ContainerShape::Bottle,
            'base_color' => '#e8b4a6',
            'material' => Material::Glossy,
            'animation_preset' => AnimationPreset::Rotate,
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ProductStatus::Draft, 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(['status' => ProductStatus::Published, 'published_at' => now()->addWeek()]);
    }
}
