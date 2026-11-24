<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shows BPOM and halal badges linking to the official lookup sites', function () {
    $product = Product::factory()->create([
        'bpom_number' => 'NA18230100123',
        'halal_number' => 'ID00410000123450124',
    ]);

    $this->get("/produk/{$product->slug}")
        ->assertOk()
        ->assertSee('NA18230100123')
        ->assertSee('href="'.Product::BPOM_CHECK_URL.'"', false)
        ->assertSee('href="'.Product::HALAL_CHECK_URL.'"', false)
        ->assertSee('Bersertifikat Halal');
});

it('renders skincare details in the visitor language', function () {
    $product = Product::factory()->create([
        'skin_types' => ['oily', 'sensitive'],
        'skin_concerns' => ['acne'],
        'claims' => ['non_comedogenic'],
        'key_ingredients' => [
            ['name' => 'Niacinamide', 'concentration' => '5%', 'benefit' => ['id' => 'Mencerahkan kulit', 'en' => 'Brightens skin']],
        ],
        'how_to_use' => ['id' => 'Oleskan 2-3 tetes setiap pagi.', 'en' => 'Apply 2-3 drops every morning.'],
        'warnings' => ['id' => 'Hanya untuk pemakaian luar.', 'en' => 'For external use only.'],
    ]);

    $this->get("/produk/{$product->slug}")
        ->assertOk()
        ->assertSeeInOrder(['Berminyak', 'Sensitif', 'Jerawat'])
        ->assertSee('Non-komedogenik')
        ->assertSeeInOrder(['Niacinamide', '5%', 'Mencerahkan kulit'])
        ->assertSee('Oleskan 2-3 tetes setiap pagi.')
        ->assertSee('Hanya untuk pemakaian luar.');

    $this->get("/en/products/{$product->slug}")
        ->assertSee('Oily')
        ->assertSee('Brightens skin')
        ->assertSee('Apply 2-3 drops every morning.');
});

it('shows the first variant price, size and own BPOM number, falling back to the product', function () {
    $product = Product::factory()->create(['price' => 99000, 'net_content' => '30 ml', 'bpom_number' => 'NA11111111111']);
    ProductVariant::query()->create([
        'product_id' => $product->id, 'label' => ['id' => '01 Ivory'], 'color' => '#f1dcc8', 'sort_order' => 0,
        'price' => 129000, 'net_content' => '15 g', 'bpom_number' => 'NA22222222222',
    ]);
    $plain = ProductVariant::query()->create([
        'product_id' => $product->id, 'label' => ['id' => '02 Natural'], 'color' => '#d8b9a6', 'sort_order' => 1,
    ]);

    $this->get("/produk/{$product->slug}")
        ->assertOk()
        ->assertSee('Rp 129.000')
        ->assertSee('15 g')
        ->assertSee('NA22222222222');

    expect($plain->pickerData($product))
        ->toMatchArray(['price' => 'Rp 99.000', 'netContent' => '30 ml', 'bpomNumber' => 'NA11111111111', 'marketplaceUrl' => null]);
});

it('shows a "from" price on cards when variants are priced differently', function () {
    $product = Product::factory()->create(['price' => 99000]);
    ProductVariant::query()->create([
        'product_id' => $product->id, 'label' => ['id' => 'Besar'], 'color' => '#ffffff', 'price' => 189000,
    ]);

    $this->get('/produk')->assertSee('Mulai Rp 99.000');
    $this->get('/en/products')->assertSee('From Rp 99.000');
});

it('lists only published FAQs and testimonials of the product', function () {
    $product = Product::factory()->create();
    Faq::factory()->for($product)->create(['question' => ['id' => 'Aman untuk ibu hamil?']]);
    Faq::factory()->for($product)->hidden()->create(['question' => ['id' => 'Pertanyaan tersembunyi?']]);
    Faq::factory()->create(['question' => ['id' => 'Pertanyaan umum?']]);
    Testimonial::factory()->for($product)->create(['content' => ['id' => 'Cocok di kulitku']]);
    Testimonial::factory()->for($product)->hidden()->create(['content' => ['id' => 'Testimoni draf']]);

    $this->get("/produk/{$product->slug}")
        ->assertSee('Aman untuk ibu hamil?')
        ->assertDontSee('Pertanyaan tersembunyi?')
        ->assertDontSee('Pertanyaan umum?')
        ->assertSee('Cocok di kulitku')
        ->assertDontSee('Testimoni draf');
});

it('lets an admin save beauty details with a normalized BPOM number', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['id' => 'Serum Detail', 'en' => 'Detail Serum'],
            'slug' => 'serum-detail',
            'category_id' => $category->id,
            'short_description' => ['id' => 'Deskripsi', 'en' => 'Description'],
            'status' => 'draft',
            'render_mode' => 'image',
            'bpom_number' => 'na 18230100123',
            'net_content' => '30 ml',
            'skin_types' => ['oily'],
            'claims' => ['vegan'],
            'key_ingredients' => [['name' => 'Centella', 'concentration' => null, 'benefit' => ['id' => 'Menenangkan', 'en' => 'Soothes']]],
            'how_to_use' => ['id' => 'Pakai malam hari', 'en' => 'Use at night'],
            'gallery' => [UploadedFile::fake()->image('photo.jpg')],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('slug', 'serum-detail')->firstOrFail();

    expect($product->bpom_number)->toBe('NA18230100123')
        ->and($product->skin_types)->toBe(['oily'])
        ->and($product->key_ingredients[0]['name'])->toBe('Centella')
        ->and($product->getTranslations('how_to_use'))->toMatchArray(['id' => 'Pakai malam hari', 'en' => 'Use at night']);
});

it('rejects a BPOM number in the wrong format', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['id' => 'Serum Salah'],
            'slug' => 'serum-salah',
            'category_id' => $category->id,
            'short_description' => ['id' => 'Deskripsi'],
            'status' => 'draft',
            'render_mode' => 'image',
            'bpom_number' => 'BPOM-123',
            'gallery' => [UploadedFile::fake()->image('photo.jpg')],
        ])
        ->call('create')
        ->assertHasFormErrors(['bpom_number']);
});
