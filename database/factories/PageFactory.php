<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PageKey;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => PageKey::About,
            'title' => ['id' => 'Tentang Kami', 'en' => 'About Us'],
            'intro' => ['id' => 'Cerita brand kami.', 'en' => 'Our brand story.'],
            'content' => ['id' => '<p>Isi halaman.</p>', 'en' => '<p>Page content.</p>'],
        ];
    }

    public function key(PageKey $key): static
    {
        return $this->state(['key' => $key]);
    }
}
