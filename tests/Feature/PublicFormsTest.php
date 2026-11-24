<?php

use App\Enums\ApplicationStatus;
use App\Models\ContactMessage;
use App\Models\ResellerApplication;
use App\Models\Subscriber;

it('stores a contact message and confirms it to the visitor', function () {
    $this->post('/kontak', [
        'name' => 'Sari',
        'phone' => '0812 3456 7890',
        'message' => 'Apakah serum ini aman untuk kulit sensitif?',
    ])
        ->assertRedirect(route('contact'))
        ->assertSessionHas('form_sent', 'contact');

    expect(ContactMessage::query()->sole())
        ->name->toBe('Sari')
        ->locale->toBe('id')
        ->read_at->toBeNull();

    $this->followingRedirects()
        ->post('/en/contact', ['name' => 'Ann', 'email' => 'ann@example.com', 'message' => 'Do you ship to Singapore?'])
        ->assertSee('Thank you! We received your message');
});

it('asks for an email or phone number in Indonesian', function () {
    $this->from('/kontak')
        ->post('/kontak', ['name' => 'Sari', 'message' => 'Halo, saya mau bertanya.'])
        ->assertRedirect('/kontak')
        ->assertSessionHasErrors([
            'email' => 'Email wajib diisi jika nomor WhatsApp tidak diisi.',
            'phone' => 'Nomor WhatsApp wajib diisi jika email tidak diisi.',
        ]);

    expect(ContactMessage::query()->count())->toBe(0);
});

it('rejects submissions that fill the hidden honeypot field', function () {
    $this->post('/kontak', [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'message' => 'Buy cheap followers now!!!',
        'website' => 'https://spam.example',
    ])->assertSessionHasErrors('website');

    expect(ContactMessage::query()->count())->toBe(0);
});

it('rate limits the public forms per visitor', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post('/newsletter', ['email' => "user{$attempt}@example.com"])->assertRedirect();
    }

    $this->post('/newsletter', ['email' => 'user6@example.com'])->assertTooManyRequests();

    expect(Subscriber::query()->count())->toBe(5);
});

it('stores a reseller application as new', function () {
    $this->post('/reseller', [
        'name' => 'Dewi',
        'phone' => '081298765432',
        'city' => 'Surabaya',
        'business_type' => 'online_shop',
        'shop_url' => 'https://shopee.co.id/dewibeauty',
    ])
        ->assertRedirect(route('reseller'))
        ->assertSessionHas('form_sent', 'reseller');

    expect(ResellerApplication::query()->sole())
        ->status->toBe(ApplicationStatus::New)
        ->city->toBe('Surabaya');
});

it('rejects an unknown reseller business type and a non-http shop link', function () {
    $this->post('/reseller', [
        'name' => 'Dewi',
        'phone' => '081298765432',
        'city' => 'Surabaya',
        'business_type' => 'pyramid_scheme',
        'shop_url' => 'javascript:alert(1)',
    ])->assertSessionHasErrors(['business_type', 'shop_url']);

    expect(ResellerApplication::query()->count())->toBe(0);
});

it('subscribes an email once, case-insensitively', function () {
    $this->from('/produk')->post('/newsletter', ['email' => 'Rina@Example.com'])
        ->assertRedirect(url('/produk').'#newsletter')
        ->assertSessionHas('form_sent', 'newsletter');

    $this->post('/newsletter', ['email' => 'rina@example.com'])->assertSessionHasNoErrors();

    expect(Subscriber::query()->pluck('email')->all())->toBe(['rina@example.com']);
});

it('never redirects a newsletter signup to another site', function () {
    $this->withHeader('referer', 'https://evil.example/phish')
        ->post('/newsletter', ['email' => 'rina@example.com'])
        ->assertRedirect(route('home').'#newsletter');
});
