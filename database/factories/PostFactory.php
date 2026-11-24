<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title($this->faker->unique()->words(4, true));

        return [
            'title' => ['id' => $title, 'en' => $title.' EN'],
            'slug' => Str::slug($title),
            'excerpt' => ['id' => 'Ringkasan '.$title, 'en' => 'Excerpt '.$title],
            'content' => ['id' => '<p>Isi artikel '.$title.'</p>', 'en' => '<p>Article body '.$title.'</p>'],
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ProductStatus::Draft, 'published_at' => null]);
    }
}
