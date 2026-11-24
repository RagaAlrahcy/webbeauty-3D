<?php

use App\Enums\ApplicationStatus;
use App\Enums\PageKey;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\ResellerApplications\Pages\ManageResellerApplications;
use App\Filament\Resources\Subscribers\Pages\ManageSubscribers;
use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Page;
use App\Models\ResellerApplication;
use App\Models\Subscriber;
use App\Models\Testimonial;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

it('lets admins open the content and inbox pages', function (string $url) {
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get($url)
        ->assertOk();
})->with([
    'articles' => '/admin/posts',
    'new article' => '/admin/posts/create',
    'testimonials' => '/admin/testimonials',
    'new testimonial' => '/admin/testimonials/create',
    'faq' => '/admin/faqs',
    'pages' => '/admin/pages',
    'promotions' => '/admin/promotions',
    'new promotion' => '/admin/promotions/create',
    'official stores' => '/admin/official-stores',
    'contact messages' => '/admin/contact-messages',
    'reseller applications' => '/admin/reseller-applications',
    'subscribers' => '/admin/subscribers',
]);

it('forbids non-admin users from the inbox', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/admin/contact-messages')
        ->assertForbidden();
});

it('lets an admin create a testimonial with before and after photos', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(CreateTestimonial::class)
        ->fillForm([
            'customer_name' => 'Maya',
            'content' => ['id' => 'Jerawat berkurang', 'en' => 'Fewer breakouts'],
            'rating' => 5,
            'before' => [UploadedFile::fake()->image('before.jpg')],
            'after' => [UploadedFile::fake()->image('after.jpg')],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Testimonial::query()->sole()->hasBeforeAfter())->toBeTrue();
});

it('creates a general FAQ from the modal with both languages', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(ManageFaqs::class)
        ->callAction('create', data: [
            'question' => ['id' => 'Apakah halal?', 'en' => 'Is it halal?'],
            'answer' => ['id' => 'Ya.', 'en' => 'Yes.'],
        ])
        ->assertHasNoFormErrors();

    expect(Faq::query()->sole())
        ->product_id->toBeNull()
        ->getTranslations('question')->toMatchArray(['id' => 'Apakah halal?', 'en' => 'Is it halal?']);
});

it('allows each content page only once', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    Page::factory()->key(PageKey::About)->create();

    Livewire::test(CreatePage::class)
        ->fillForm(['key' => PageKey::About->value, 'title' => ['id' => 'Tentang Kami Lagi']])
        ->call('create')
        ->assertHasFormErrors(['key' => 'unique']);
});

it('marks a contact message as read', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $message = ContactMessage::query()->create(['name' => 'Sari', 'email' => 'sari@example.com', 'message' => 'Halo kak', 'locale' => 'id']);

    Livewire::test(ManageContactMessages::class)
        ->callAction(TestAction::make('markRead')->table($message));

    expect($message->fresh()->read_at)->not->toBeNull();
});

it('updates a reseller application status and notes', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $application = ResellerApplication::query()->create([
        'name' => 'Dewi', 'phone' => '0812', 'city' => 'Solo', 'business_type' => 'personal', 'locale' => 'id',
    ]);

    Livewire::test(ManageResellerApplications::class)
        ->callAction(TestAction::make('edit')->table($application), data: [
            'status' => ApplicationStatus::Approved->value,
            'notes' => 'Kirim katalog harga reseller',
        ])
        ->assertHasNoFormErrors();

    expect($application->fresh())
        ->status->toBe(ApplicationStatus::Approved)
        ->notes->toBe('Kirim katalog harga reseller');
});

it('exports subscribers as CSV with spreadsheet formulas neutralized', function () {
    Subscriber::query()->create(['email' => 'rina@example.com', 'locale' => 'id']);
    Subscriber::query()->create(['email' => '=cmd@example.com', 'locale' => 'en']);

    $response = ManageSubscribers::csvDownload();

    ob_start();
    $response->sendContent();
    $csv = (string) ob_get_clean();

    expect($csv)
        ->toContain("email,locale,subscribed_at\n")
        ->toContain('rina@example.com,id,')
        ->toContain("'=cmd@example.com,en,");
});

it('rejects tracking IDs in the wrong format', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['brand_name' => 'Brand', 'primary_color' => '#123456', 'accent_color' => '#abcdef', 'ga4_measurement_id' => 'UA-12345', 'meta_pixel_id' => 'abc'])
        ->call('save')
        ->assertHasFormErrors(['ga4_measurement_id', 'meta_pixel_id']);
});
