<?php

use App\Enums\ProductStatus;
use App\Filament\Pages\ManageHomepage;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

function admin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

/**
 * @return array<string, mixed>
 */
function productFormData(Category $category, array $overrides = []): array
{
    return [
        'name' => ['id' => 'Serum Baru', 'en' => 'New Serum'],
        'slug' => 'serum-baru',
        'category_id' => $category->id,
        'short_description' => ['id' => 'Deskripsi singkat', 'en' => 'Short description'],
        'status' => ProductStatus::Published->value,
        'published_at' => now()->subHour()->toDateTimeString(),
        'render_mode' => 'procedural',
        'container_shape' => 'bottle',
        'base_color' => '#e8b4a6',
        'material' => 'glossy',
        'animation_preset' => 'rotate',
        ...$overrides,
    ];
}

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('forbids non-admin users', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/admin')
        ->assertForbidden();
});

it('lets admins open admin pages', function () {
    $this->actingAs(admin());

    foreach (['/admin', '/admin/products', '/admin/products/create', '/admin/categories', '/admin/manage-site-settings', '/admin/manage-homepage'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('lets an admin create a product with photos and translations', function () {
    $this->actingAs(admin());
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            ...productFormData($category),
            'gallery' => [UploadedFile::fake()->image('photo.jpg', 800, 800)],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('slug', 'serum-baru')->firstOrFail();

    expect($product->getTranslations('name'))->toMatchArray(['id' => 'Serum Baru', 'en' => 'New Serum'])
        ->and($product->getMedia('gallery'))->toHaveCount(1);
});

it('fills both languages when editing', function () {
    $this->actingAs(admin());
    $product = Product::factory()->create(['name' => ['id' => 'Nama ID', 'en' => 'Name EN']]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertSchemaStateSet(['name.id' => 'Nama ID', 'name.en' => 'Name EN']);
});

it('rejects a model file that is not .glb', function () {
    $this->actingAs(admin());
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            ...productFormData($category, ['render_mode' => 'model3d']),
            'gallery' => [UploadedFile::fake()->image('photo.jpg')],
            'model' => [UploadedFile::fake()->create('model.obj', 100, 'application/octet-stream')],
        ])
        ->call('create')
        ->assertHasFormErrors(['model']);
});

it('rejects a non-hex base color', function () {
    $this->actingAs(admin());
    $category = Category::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            ...productFormData($category, ['base_color' => 'pink']),
            'gallery' => [UploadedFile::fake()->image('photo.jpg')],
        ])
        ->call('create')
        ->assertHasFormErrors(['base_color']);
});

it('uses the bundled favicon until one is uploaded in site settings', function () {
    $this->get('/')->assertSee(asset('favicon.svg'), false);

    $this->actingAs(admin());

    Livewire::test(ManageSiteSettings::class)
        ->fillForm([
            'brand_name' => 'Brand Uji',
            'primary_color' => '#123456',
            'accent_color' => '#abcdef',
            'favicon' => [UploadedFile::fake()->image('icon.png', 256, 256)],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $favicon = SiteSetting::current()->getFirstMedia('favicon');

    expect($favicon->hasGeneratedConversion('icon'))->toBeTrue();

    $this->get('/')
        ->assertSee($favicon->getUrl('icon'), false)
        ->assertDontSee(asset('favicon.svg'), false);
    $this->get('/admin')->assertSee($favicon->getUrl('icon'), false);
});

it('saves site settings and homepage', function () {
    $this->actingAs(admin());

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['brand_name' => 'Brand Uji', 'primary_color' => '#123456', 'accent_color' => '#abcdef'])
        ->call('save')
        ->assertHasNoFormErrors();

    Livewire::test(ManageHomepage::class)
        ->fillForm(['headline' => ['id' => 'Judul', 'en' => 'Headline']])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertSee('Brand Uji')->assertSee('Judul');
    $this->get('/en')->assertSee('Headline');
});
