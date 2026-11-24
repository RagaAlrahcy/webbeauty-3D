<?php

use App\Enums\PageKey;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\BusinessOverview;
use App\Filament\Widgets\ContentOverview;
use App\Filament\Widgets\InboxActivityChart;
use App\Filament\Widgets\LatestContactMessages;
use App\Filament\Widgets\LatestResellerApplications;
use App\Filament\Widgets\ProductsByCategoryChart;
use App\Filament\Widgets\SiteHealthChecklist;
use App\Models\ContactMessage;
use App\Models\OfficialStore;
use App\Models\Page;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\SiteHealth;
use Livewire\Livewire;

/**
 * Settings and content that leave nothing for the checklist to report.
 */
function completeSite(): void
{
    SiteSetting::current()->update(['whatsapp_number' => '081234567890', 'ga4_measurement_id' => 'G-AB12CD34EF']);
    OfficialStore::factory()->create();

    foreach ([PageKey::About, PageKey::Privacy, PageKey::Terms] as $key) {
        Page::factory()->key($key)->create();
    }
}

it('renders every dashboard widget for an admin', function (string $widget) {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    Product::factory()->create();
    ContactMessage::query()->create(['name' => 'Sari', 'email' => 'sari@example.com', 'message' => 'Halo kak', 'locale' => 'id']);

    Livewire::test($widget)->assertOk();
})->with([
    'business overview' => BusinessOverview::class,
    'content overview' => ContentOverview::class,
    'site health checklist' => SiteHealthChecklist::class,
    'inbox activity chart' => InboxActivityChart::class,
    'products by category chart' => ProductsByCategoryChart::class,
    'latest messages' => LatestContactMessages::class,
    'latest reseller applications' => LatestResellerApplications::class,
]);

it('shows the unread message count and the newest message', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    ContactMessage::query()->create(['name' => 'Sari', 'email' => 'sari@example.com', 'message' => 'Apakah ready stok?', 'locale' => 'id']);
    ContactMessage::query()->create(['name' => 'Budi', 'email' => 'budi@example.com', 'message' => 'Sudah dibalas', 'locale' => 'id', 'read_at' => now()]);

    Livewire::test(BusinessOverview::class)
        ->assertSeeInOrder(['Pesan belum dibaca', '1', '2 pesan dalam 30 hari terakhir']);

    Livewire::test(LatestContactMessages::class)
        ->assertCanSeeTableRecords(ContactMessage::query()->get());
});

it('lists live products without a BPOM number with links to edit them', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    completeSite();
    Product::factory()->create(['name' => ['id' => 'Serum Tanpa BPOM'], 'bpom_number' => null]);
    Product::factory()->create(['name' => ['id' => 'Serum Terdaftar'], 'bpom_number' => 'NA18230100123']);
    Product::factory()->draft()->create(['name' => ['id' => 'Draf Tanpa BPOM'], 'bpom_number' => null]);

    Livewire::test(SiteHealthChecklist::class)
        ->assertSee('1 produk tayang belum punya nomor BPOM')
        ->assertSee('Serum Tanpa BPOM')
        ->assertDontSee('Draf Tanpa BPOM');

    $issue = collect(app(SiteHealth::class)->issues())->firstWhere('title', '1 produk tayang belum punya nomor BPOM');

    expect($issue['items'])->toBe([[
        'label' => 'Serum Tanpa BPOM',
        'url' => ProductResource::getUrl('edit', ['record' => Product::query()->where('bpom_number', null)->published()->sole()]),
    ]]);
});

it('reports missing WhatsApp, pages, stores and tracking as checklist issues', function () {
    $issues = collect(app(SiteHealth::class)->issues());

    expect($issues->firstWhere('level', SiteHealth::DANGER)['title'])->toBe('Nomor WhatsApp belum diisi')
        ->and($issues->pluck('title'))
        ->toContain('Halaman belum dibuat: Tentang Kami, Kebijakan Privasi, Syarat & Ketentuan')
        ->toContain('Belum ada toko resmi')
        ->toContain('Analytics belum aktif');
});

it('flags only running promotions that end within three days', function () {
    completeSite();
    Promotion::factory()->create(['title' => ['id' => 'Promo Akhir Pekan'], 'ends_at' => now()->addDays(2)]);
    Promotion::factory()->create(['title' => ['id' => 'Promo Panjang'], 'ends_at' => now()->addDays(10)]);
    Promotion::factory()->create(['title' => ['id' => 'Promo Nonaktif'], 'ends_at' => now()->addDay(), 'is_active' => false]);

    $issue = collect(app(SiteHealth::class)->issues())->firstWhere('title', '1 promo berakhir dalam 3 hari');

    expect($issue)->not->toBeNull()
        ->and($issue['items'])->toHaveCount(1)
        ->and($issue['items'][0]['label'])->toStartWith('Promo Akhir Pekan');
});

it('shows an all-good message when nothing needs attention', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    completeSite();
    Product::factory()->create([
        'bpom_number' => 'NA18230100123',
        'skin_types' => ['normal'],
        'how_to_use' => ['id' => 'Oleskan tipis.'],
    ]);

    expect(app(SiteHealth::class)->issues())->toBe([]);

    Livewire::test(SiteHealthChecklist::class)->assertSee('Semua beres!');
});

it('counts records per day with zeros for quiet days', function () {
    $this->freezeTime();
    $subscribeAt = function (string $email, int $daysAgo): void {
        $this->travelTo(now()->subDays($daysAgo), fn () => Subscriber::query()->create(['email' => $email, 'locale' => 'id']));
    };

    $subscribeAt('a@example.com', 2);
    $subscribeAt('b@example.com', 2);
    $subscribeAt('c@example.com', 0);
    $subscribeAt('old@example.com', 10);

    expect(InboxActivityChart::countsPerDay(Subscriber::query(), 7))->toBe([
        now()->subDays(6)->toDateString() => 0,
        now()->subDays(5)->toDateString() => 0,
        now()->subDays(4)->toDateString() => 0,
        now()->subDays(3)->toDateString() => 0,
        now()->subDays(2)->toDateString() => 2,
        now()->subDay()->toDateString() => 0,
        now()->toDateString() => 1,
    ]);
});
