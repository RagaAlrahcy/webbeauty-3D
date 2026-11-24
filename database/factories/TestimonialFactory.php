<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_name' => $this->faker->firstName(),
            'customer_info' => ['id' => 'Kulit berminyak', 'en' => 'Oily skin'],
            'content' => ['id' => 'Kulit terasa lebih lembap.', 'en' => 'My skin feels more hydrated.'],
            'rating' => 5,
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function hidden(): static
    {
        return $this->state(['is_published' => false]);
    }
}
