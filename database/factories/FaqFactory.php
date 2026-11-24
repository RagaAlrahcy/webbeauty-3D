<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        $question = rtrim($this->faker->unique()->sentence(5), '.');

        return [
            'question' => ['id' => $question.'?', 'en' => $question.' EN?'],
            'answer' => ['id' => 'Jawaban untuk '.$question, 'en' => 'Answer for '.$question],
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_published' => false]);
    }
}
