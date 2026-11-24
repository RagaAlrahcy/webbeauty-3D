<?php

use App\Models\Faq;
use App\Models\OfficialStore;
use App\Models\Page;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Testimonial;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds demo content that renders and can be re-run without duplicates', function () {
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);
    $counts = fn (): array => [
        Faq::query()->count(), Page::query()->count(), OfficialStore::query()->count(),
        Testimonial::query()->count(), Promotion::query()->count(),
    ];
    $first = $counts();

    $this->seed(DatabaseSeeder::class);

    expect($counts())->toBe($first)
        ->and(Product::query()->where('slug', 'glow-renewal-serum')->value('bpom_number'))->toBe('NA18230100001');

    $this->get('/produk/glow-renewal-serum')->assertOk()->assertSee('Niacinamide');
    $this->get('/tentang-kami')->assertOk();
    $this->get('/faq')->assertOk();
    $this->get('/beli-di-mana')->assertOk()->assertSee('Lumière Official Shop');
});
