<?php

use App\Models\Product;
use Illuminate\Http\UploadedFile;

it('shows only published products in the catalog', function () {
    $published = Product::factory()->create(['name' => ['id' => 'Produk Terbit', 'en' => 'Published Product']]);
    Product::factory()->draft()->create(['name' => ['id' => 'Produk Draf', 'en' => 'Draft Product']]);

    $this->get('/produk')
        ->assertOk()
        ->assertSee('Produk Terbit')
        ->assertDontSee('Produk Draf');

    $this->get('/en/products')
        ->assertOk()
        ->assertSee('Published Product')
        ->assertDontSee('Draft Product');
});

it('returns 404 for draft and scheduled products', function () {
    $draft = Product::factory()->draft()->create();
    $scheduled = Product::factory()->scheduled()->create();

    $this->get("/produk/{$draft->slug}")->assertNotFound();
    $this->get("/produk/{$scheduled->slug}")->assertNotFound();
});

it('renders the product page with fallback image, viewer config and SEO', function () {
    $product = Product::factory()->create([
        'name' => ['id' => 'Serum Uji', 'en' => 'Test Serum'],
        'meta_description' => ['id' => 'Meta deskripsi uji', 'en' => 'Test meta description'],
    ]);
    $product->addMedia(UploadedFile::fake()->image('serum.jpg', 600, 600))->toMediaCollection('gallery');

    $response = $this->get("/produk/{$product->slug}")->assertOk();

    $response
        ->assertSee('<title>Serum Uji', false)
        ->assertSee('<meta name="description" content="Meta deskripsi uji">', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('"@type":"Product"', false)
        ->assertSee('class="viewer-fallback"', false)
        ->assertSee('data-product-viewer', false);

    preg_match("/data-config='([^']+)'/", $response->getContent(), $matches);
    $config = json_decode(html_entity_decode($matches[1] ?? ''), true);

    expect($config)->toBeArray()
        ->and($config['renderMode'])->toBe('procedural')
        ->and($config['containerShape'])->toBe('bottle');
});

it('serves the english version under /en', function () {
    $product = Product::factory()->create(['name' => ['id' => 'Nama Indonesia', 'en' => 'English Name']]);

    $this->get("/en/products/{$product->slug}")
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertSee('English Name');
});

it('renders the homepage and sitemap', function () {
    $product = Product::factory()->create(['is_featured' => true]);

    $this->get('/')->assertOk();
    $this->get('/en')->assertOk();
    $this->get('/sitemap.xml')->assertOk()->assertSee($product->slug);
});

it('keeps image-only products static (no 3D viewer)', function () {
    $product = Product::factory()->create(['render_mode' => 'image']);

    $this->get("/produk/{$product->slug}")
        ->assertOk()
        ->assertDontSee('data-product-viewer', false);
});
