<?php

use App\Enums\PageKey;
use App\Models\Faq;
use App\Models\OfficialStore;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SiteSetting;
use App\Models\Testimonial;

it('renders content pages in both languages and links them in the footer', function () {
    Page::factory()->key(PageKey::Privacy)->create([
        'title' => ['id' => 'Kebijakan Privasi', 'en' => 'Privacy Policy'],
        'content' => ['id' => '<p>Data kamu aman.</p>', 'en' => '<p>Your data is safe.</p>'],
    ]);

    $this->get('/kebijakan-privasi')->assertOk()->assertSee('Data kamu aman.');
    $this->get('/en/privacy-policy')->assertOk()->assertSee('Your data is safe.');
    $this->get('/')->assertSee('href="'.route('privacy').'"', false);
});

it('returns 404 for content pages that were never created and hides their links', function () {
    $this->get('/tentang-kami')->assertNotFound();
    $this->get('/syarat-dan-ketentuan')->assertNotFound();
    $this->get('/')
        ->assertDontSee('href="'.route('about').'"', false)
        ->assertDontSee('href="'.route('terms').'"', false);
});

it('strips scripts from rich page content', function () {
    Page::factory()->key(PageKey::About)->create([
        'content' => ['id' => '<p>Halo</p><script>alert(1)</script>'],
    ]);

    $this->get('/tentang-kami')
        ->assertSee('<p>Halo</p>', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('lists published general questions on the FAQ page', function () {
    Faq::factory()->create(['question' => ['id' => 'Berapa lama pengiriman?']]);
    Faq::factory()->hidden()->create(['question' => ['id' => 'Pertanyaan draf?']]);
    Faq::factory()->for(Product::factory())->create(['question' => ['id' => 'Pertanyaan produk?']]);

    $this->get('/faq')
        ->assertOk()
        ->assertSee('Berapa lama pengiriman?')
        ->assertDontSee('Pertanyaan draf?')
        ->assertDontSee('Pertanyaan produk?');
});

it('returns 404 for the FAQ page when there are no general questions', function () {
    $this->get('/faq')->assertNotFound();
});

it('groups published official stores by type on the where-to-buy page', function () {
    OfficialStore::factory()->create(['name' => 'Toko Shopee Resmi']);
    OfficialStore::factory()->offline()->create(['name' => 'Butik Bandung']);
    OfficialStore::factory()->create(['name' => 'Toko Disembunyikan', 'is_published' => false]);

    $this->get('/beli-di-mana')
        ->assertOk()
        ->assertSeeInOrder(['Toko online resmi', 'Toko Shopee Resmi', 'Toko offline', 'Butik Bandung'])
        ->assertDontSee('Toko Disembunyikan');
});

it('shows published articles with their related products and hides drafts', function () {
    $product = Product::factory()->create(['name' => ['id' => 'Serum Artikel']]);
    $post = Post::factory()->create(['title' => ['id' => 'Cara Memakai Niacinamide', 'en' => 'How to Use Niacinamide']]);
    $post->products()->attach($product);
    $draft = Post::factory()->draft()->create(['title' => ['id' => 'Artikel Draf']]);

    $this->get('/artikel')
        ->assertOk()
        ->assertSee('Cara Memakai Niacinamide')
        ->assertDontSee('Artikel Draf');

    $this->get("/artikel/{$post->slug}")
        ->assertOk()
        ->assertSee('"@type":"Article"', false)
        ->assertSee('Serum Artikel');

    $this->get("/en/articles/{$post->slug}")->assertOk()->assertSee('How to Use Niacinamide');
    $this->get("/artikel/{$draft->slug}")->assertNotFound();
});

it('shows only promotions inside their schedule', function () {
    Promotion::factory()->create(['title' => ['id' => 'Promo Berjalan']]);
    Promotion::factory()->create(['title' => ['id' => 'Promo Selesai'], 'ends_at' => now()->subMinute()]);
    Promotion::factory()->create(['title' => ['id' => 'Promo Nanti'], 'starts_at' => now()->addDay()]);
    Promotion::factory()->create(['title' => ['id' => 'Promo Nonaktif'], 'is_active' => false]);
    Promotion::factory()->home()->create(['title' => ['id' => 'Banner Beranda']]);

    $this->get('/produk')
        ->assertSee('Promo Berjalan')
        ->assertDontSee('Promo Selesai')
        ->assertDontSee('Promo Nanti')
        ->assertDontSee('Promo Nonaktif')
        ->assertDontSee('Banner Beranda');

    $this->get('/')->assertSee('Banner Beranda');
});

it('starts showing a scheduled promotion once its start time passes', function () {
    Promotion::factory()->create(['title' => ['id' => 'Flash Sale'], 'starts_at' => now()->addHour()]);

    $this->get('/')->assertDontSee('Flash Sale');

    $this->travel(2)->hours();

    $this->get('/')->assertSee('Flash Sale');
});

it('shows only featured testimonials on the homepage', function () {
    Testimonial::factory()->featured()->create(['content' => ['id' => 'Favoritku!']]);
    Testimonial::factory()->create(['content' => ['id' => 'Bukan unggulan']]);

    $this->get('/')
        ->assertSee('Favoritku!')
        ->assertDontSee('Bukan unggulan');
});

it('adds articles and created pages to the sitemap', function () {
    $post = Post::factory()->create();
    Page::factory()->key(PageKey::Terms)->create();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee($post->slug)
        ->assertSee(route('terms'))
        ->assertDontSee(route('about'));
});

it('loads analytics scripts only for valid tracking IDs', function () {
    $this->get('/')->assertDontSee('googletagmanager.com', false);

    $settings = SiteSetting::current();
    $settings->update(['ga4_measurement_id' => 'G-AB12CD34EF', 'meta_pixel_id' => '1234567890']);

    $this->get('/')
        ->assertSee('googletagmanager.com/gtag/js?id=G-AB12CD34EF', false)
        ->assertSee("fbq('init', '1234567890')", false);

    // A value that slipped past validation (e.g. edited in the database) is never printed.
    $settings->update(['ga4_measurement_id' => "G-1');alert('x"]);

    $this->get('/')->assertDontSee('googletagmanager.com', false);
});
