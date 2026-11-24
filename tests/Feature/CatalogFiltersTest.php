<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

it('filters the catalog by skin type and skin concern', function () {
    Product::factory()->create(['name' => ['id' => 'Gel Minyak'], 'skin_types' => ['oily'], 'skin_concerns' => ['acne']]);
    Product::factory()->create(['name' => ['id' => 'Krim Kering'], 'skin_types' => ['dry'], 'skin_concerns' => ['dehydration']]);

    $this->get('/produk?skin=oily')
        ->assertSee('Gel Minyak')
        ->assertDontSee('Krim Kering');

    $this->get('/produk?concern=dehydration')
        ->assertSee('Krim Kering')
        ->assertDontSee('Gel Minyak');
});

it('only offers skin types and concerns that some product has', function () {
    Product::factory()->create(['skin_types' => ['sensitive']]);

    $this->get('/produk')
        ->assertSee('<option value="sensitive"', false)
        ->assertDontSee('<option value="oily"', false)
        ->assertDontSee('name="concern"', false);
});

it('searches names and ingredients in every language', function () {
    Product::factory()->create([
        'name' => ['id' => 'Serum Cerah', 'en' => 'Bright Serum'],
        'ingredients' => ['id' => ['Ekstrak Teh Hijau'], 'en' => ['Green Tea Extract']],
    ]);
    Product::factory()->create(['name' => ['id' => 'Lip Balm Mawar', 'en' => 'Rose Lip Balm']]);

    $this->get('/produk?q=green+tea')
        ->assertSee('Serum Cerah')
        ->assertDontSee('Lip Balm Mawar');

    $this->get('/en/products?q=bright')
        ->assertSee('Bright Serum')
        ->assertDontSee('Rose Lip Balm');

    $this->get('/produk?q=tidakada')
        ->assertSee('Tidak ada produk yang cocok');
});

it('sorts by the lowest price including variant prices, unpriced products last', function () {
    Product::factory()->create(['name' => ['id' => 'Produk Mahal'], 'price' => 300000]);
    Product::factory()->create(['name' => ['id' => 'Produk Tanpa Harga'], 'price' => null]);
    $varianted = Product::factory()->create(['name' => ['id' => 'Produk Varian'], 'price' => 250000]);
    ProductVariant::query()->create(['product_id' => $varianted->id, 'label' => ['id' => 'Mini'], 'color' => '#ffffff', 'price' => 50000]);

    $this->get('/produk?sort=price_low')
        ->assertSeeInOrder(['Produk Varian', 'Produk Mahal', 'Produk Tanpa Harga']);

    $this->get('/produk?sort=price_high')
        ->assertSeeInOrder(['Produk Mahal', 'Produk Varian', 'Produk Tanpa Harga']);
});

it('combines the category with other filters and ignores unknown values', function () {
    $serum = Category::factory()->create(['slug' => 'serum']);
    Product::factory()->for($serum)->create(['name' => ['id' => 'Serum Berminyak'], 'skin_types' => ['oily']]);
    Product::factory()->create(['name' => ['id' => 'Toner Berminyak'], 'skin_types' => ['oily']]);

    $this->get('/produk?category=serum&skin=oily')
        ->assertSee('Serum Berminyak')
        ->assertDontSee('Toner Berminyak');

    $this->get('/produk?category=tidak-ada&skin=alien&sort=random')
        ->assertOk()
        ->assertSee('Serum Berminyak')
        ->assertSee('Toner Berminyak');
});

it('paginates the catalog and keeps filters in the page links', function () {
    Product::factory()->count(13)->create(['skin_types' => ['normal']]);

    $this->get('/produk?skin=normal')
        ->assertSee('13 produk')
        ->assertSee('skin=normal&amp;page=2', false);

    $this->get('/produk?skin=normal&page=2')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});
