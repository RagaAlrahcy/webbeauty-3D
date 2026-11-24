<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MarketplacePlatform;
use App\Enums\PageKey;
use App\Enums\ProductStatus;
use App\Enums\PromotionPlacement;
use App\Enums\StoreType;
use App\Models\Faq;
use App\Models\OfficialStore;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Example beauty details, trust content and pages for the demo catalog.
 *
 * Safe to run repeatedly: records are matched by a natural key, and product
 * details are only filled where the column is still empty.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->productDetails();
        $this->testimonials();
        $this->faqs();
        $this->pages();
        $this->stores();
        $this->posts();
        $this->promotions();
    }

    private function productDetails(): void
    {
        $details = [
            'glow-renewal-serum' => [
                'bpom_number' => 'NA18230100001',
                'halal_number' => 'ID00410000123450124',
                'net_content' => '30 ml',
                'skin_types' => ['normal', 'oily', 'combination'],
                'skin_concerns' => ['dullness', 'large_pores', 'dark_spots'],
                'claims' => ['dermatologically_tested', 'non_comedogenic', 'fragrance_free'],
                'key_ingredients' => [
                    ['name' => 'Niacinamide', 'concentration' => '10%', 'benefit' => ['id' => 'Membantu mencerahkan dan menyamarkan pori.', 'en' => 'Helps brighten and refine pores.']],
                    ['name' => 'Zinc PCA', 'concentration' => '1%', 'benefit' => ['id' => 'Membantu mengontrol minyak berlebih.', 'en' => 'Helps control excess oil.']],
                ],
                'how_to_use' => [
                    'id' => "1. Bersihkan wajah.\n2. Teteskan 2–3 tetes serum ke telapak tangan.\n3. Tepuk lembut ke seluruh wajah pagi dan malam.\n4. Lanjutkan dengan pelembap dan tabir surya di pagi hari.",
                    'en' => "1. Cleanse your face.\n2. Put 2–3 drops of serum in your palm.\n3. Gently pat over the face morning and night.\n4. Follow with moisturizer and sunscreen in the morning.",
                ],
                'warnings' => [
                    'id' => 'Hanya untuk pemakaian luar. Hindari area mata. Hentikan pemakaian jika terjadi iritasi.',
                    'en' => 'For external use only. Avoid the eye area. Stop use if irritation occurs.',
                ],
            ],
            'hydra-cloud-cream' => [
                'bpom_number' => 'NA18230100002',
                'net_content' => '50 g',
                'skin_types' => ['dry', 'sensitive', 'normal'],
                'skin_concerns' => ['dehydration', 'redness'],
                'claims' => ['hypoallergenic', 'alcohol_free', 'paraben_free'],
                'key_ingredients' => [
                    ['name' => 'Ceramide NP', 'concentration' => null, 'benefit' => ['id' => 'Memperkuat skin barrier.', 'en' => 'Strengthens the skin barrier.']],
                    ['name' => 'Centella Asiatica', 'concentration' => null, 'benefit' => ['id' => 'Menenangkan kulit kemerahan.', 'en' => 'Calms redness.']],
                ],
                'how_to_use' => [
                    'id' => 'Ambil secukupnya dan ratakan ke wajah setelah serum, pagi dan malam.',
                    'en' => 'Apply an adequate amount after serum, morning and night.',
                ],
            ],
            'velvet-lip-balm' => [
                'bpom_number' => 'NA18230100003',
                'net_content' => '4 g',
                'claims' => ['vegan', 'cruelty_free'],
                'skin_concerns' => ['dehydration'],
            ],
            'silk-finish-cushion' => [
                'bpom_number' => 'NA18230100004',
                'halal_number' => 'ID00410000123460124',
                'net_content' => '15 g',
                'skin_types' => ['normal', 'oily', 'combination'],
                'claims' => ['dermatologically_tested', 'non_comedogenic'],
                'warnings' => [
                    'id' => 'Hanya untuk pemakaian luar. Tutup rapat setelah digunakan.',
                    'en' => 'For external use only. Close tightly after use.',
                ],
            ],
        ];

        foreach ($details as $slug => $values) {
            $product = Product::query()->where('slug', $slug)->first();

            if ($product === null) {
                continue;
            }

            foreach ($values as $column => $value) {
                if (blank($product->getRawOriginal($column))) {
                    $product->{$column} = $value;
                }
            }

            $product->save();
        }

        // Shade-specific prices for the cushion show the "from" price and variant switching.
        $cushion = Product::query()->where('slug', 'silk-finish-cushion')->with('variants')->first();
        $cushion?->variants->each(function ($variant, int $index): void {
            if ($index === 2 && $variant->price === null) {
                $variant->update(['price' => 239000]);
            }
        });
    }

    private function testimonials(): void
    {
        $serum = Product::query()->where('slug', 'glow-renewal-serum')->value('id');
        $cream = Product::query()->where('slug', 'hydra-cloud-cream')->value('id');

        $items = [
            ['Nadia', $serum, ['id' => 'Kulit berminyak, 26 tahun', 'en' => 'Oily skin, 26'], ['id' => 'Setelah tiga minggu pori-pori terlihat lebih halus dan wajah tidak gampang kusam.', 'en' => 'After three weeks my pores look smoother and my face is less dull.']],
            ['Putri', $cream, ['id' => 'Kulit kering & sensitif', 'en' => 'Dry & sensitive skin'], ['id' => 'Teksturnya ringan tapi lembapnya tahan seharian. Tidak perih sama sekali.', 'en' => 'Light texture but hydrates all day. No stinging at all.']],
            ['Rani', $serum, ['id' => 'Kulit kombinasi, 31 tahun', 'en' => 'Combination skin, 31'], ['id' => 'Cepat meresap, cocok dipakai sebelum makeup.', 'en' => 'Absorbs quickly, works well under makeup.']],
        ];

        foreach ($items as $order => [$name, $productId, $info, $content]) {
            Testimonial::query()->updateOrCreate(
                ['customer_name' => $name],
                ['product_id' => $productId, 'customer_info' => $info, 'content' => $content, 'rating' => 5, 'is_featured' => true, 'sort_order' => $order],
            );
        }
    }

    private function faqs(): void
    {
        $general = [
            [['id' => 'Apakah semua produk terdaftar di BPOM?', 'en' => 'Are all products BPOM registered?'], ['id' => 'Ya. Nomor notifikasi BPOM tercantum di setiap halaman produk dan bisa kamu cek langsung di situs cekbpom.pom.go.id.', 'en' => 'Yes. The BPOM notification number is listed on every product page and can be checked on cekbpom.pom.go.id.']],
            [['id' => 'Bagaimana cara memesan?', 'en' => 'How do I order?'], ['id' => 'Klik tombol "Tanya via WhatsApp" di halaman produk, atau beli melalui toko resmi kami di marketplace.', 'en' => 'Tap "Ask via WhatsApp" on a product page, or buy from our official marketplace stores.']],
            [['id' => 'Bagaimana memastikan produk yang saya beli asli?', 'en' => 'How can I be sure my product is genuine?'], ['id' => 'Beli hanya dari daftar di halaman "Beli di Mana" dan cocokkan nomor BPOM di kemasan dengan nomor di situs kami.', 'en' => 'Only buy from the stores on our "Where to Buy" page and match the BPOM number on the pack with the one on our site.']],
        ];

        foreach ($general as $order => [$question, $answer]) {
            $this->createFaqOnce(['question' => $question, 'answer' => $answer, 'sort_order' => $order]);
        }

        $serum = Product::query()->where('slug', 'glow-renewal-serum')->value('id');

        if ($serum !== null) {
            $this->createFaqOnce([
                'product_id' => $serum,
                'question' => ['id' => 'Boleh dipakai bersama retinol?', 'en' => 'Can I use it with retinol?'],
                'answer' => ['id' => 'Boleh. Gunakan serum ini di pagi hari dan retinol di malam hari untuk hasil yang nyaman.', 'en' => 'Yes. Use this serum in the morning and retinol at night for comfortable results.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createFaqOnce(array $attributes): void
    {
        if (! Faq::query()->where('question->id', $attributes['question']['id'])->exists()) {
            Faq::query()->create($attributes);
        }
    }

    private function pages(): void
    {
        $pages = [
            PageKey::About->value => [
                'title' => ['id' => 'Tentang Kami', 'en' => 'About Us'],
                'intro' => ['id' => 'Perawatan kulit yang lembut, jujur, dan dibuat untuk iklim tropis.', 'en' => 'Gentle, honest skincare made for tropical climates.'],
                'content' => [
                    'id' => '<h2>Cerita kami</h2><p>Kami percaya perawatan kulit tidak harus rumit. Setiap formula dikembangkan bersama ahli kosmetik, diuji secara dermatologis, dan terdaftar di BPOM.</p><h2>Nilai kami</h2><ul><li>Bahan aktif dengan kadar yang jelas</li><li>Tanpa uji coba pada hewan</li><li>Kemasan yang bisa didaur ulang</li></ul>',
                    'en' => '<h2>Our story</h2><p>We believe skincare does not have to be complicated. Every formula is developed with cosmetic chemists, dermatologically tested, and BPOM registered.</p><h2>Our values</h2><ul><li>Active ingredients with clear concentrations</li><li>No animal testing</li><li>Recyclable packaging</li></ul>',
                ],
            ],
            PageKey::Privacy->value => [
                'title' => ['id' => 'Kebijakan Privasi', 'en' => 'Privacy Policy'],
                'intro' => ['id' => 'Templat awal. Mohon tinjau bersama penasihat hukum sebelum dipublikasikan.', 'en' => 'Starter template. Please review with legal counsel before publishing.'],
                'content' => [
                    'id' => '<h2>Data yang kami kumpulkan</h2><p>Nama, email, nomor WhatsApp, dan isi pesan yang kamu kirim melalui formulir kontak, pendaftaran reseller, atau newsletter. Jika diaktifkan, kami juga memakai Google Analytics dan Meta Pixel untuk mengukur kunjungan.</p><h2>Tujuan penggunaan</h2><p>Membalas pertanyaan, memproses pendaftaran reseller, dan mengirim info promo bila kamu berlangganan.</p><h2>Hak kamu</h2><p>Sesuai Undang-Undang No. 27 Tahun 2022 tentang Pelindungan Data Pribadi, kamu berhak meminta akses, perbaikan, atau penghapusan data. Hubungi kami melalui halaman Kontak.</p>',
                    'en' => '<h2>Data we collect</h2><p>Your name, email, WhatsApp number and message sent through the contact, reseller or newsletter forms. When enabled, we also use Google Analytics and Meta Pixel to measure visits.</p><h2>How we use it</h2><p>To reply to questions, process reseller applications, and send promotions if you subscribed.</p><h2>Your rights</h2><p>Under Indonesian Law No. 27 of 2022 on Personal Data Protection you may request access to, correction of, or deletion of your data. Contact us through the Contact page.</p>',
                ],
            ],
            PageKey::Terms->value => [
                'title' => ['id' => 'Syarat & Ketentuan', 'en' => 'Terms & Conditions'],
                'intro' => ['id' => 'Templat awal. Mohon tinjau bersama penasihat hukum sebelum dipublikasikan.', 'en' => 'Starter template. Please review with legal counsel before publishing.'],
                'content' => [
                    'id' => '<h2>Informasi produk</h2><p>Kami berusaha menampilkan informasi produk seakurat mungkin. Hasil pemakaian dapat berbeda pada setiap orang.</p><h2>Pembelian</h2><p>Transaksi dilakukan melalui WhatsApp atau toko resmi di marketplace dan mengikuti ketentuan masing-masing platform.</p>',
                    'en' => '<h2>Product information</h2><p>We aim to show product information as accurately as possible. Results may vary from person to person.</p><h2>Purchases</h2><p>Transactions take place via WhatsApp or our official marketplace stores and follow each platform\'s terms.</p>',
                ],
            ],
            PageKey::Reseller->value => [
                'title' => ['id' => 'Jadi Reseller Resmi', 'en' => 'Become an Official Reseller'],
                'intro' => ['id' => 'Dapatkan harga khusus, materi promosi, dan dukungan langsung dari tim kami.', 'en' => 'Get special pricing, marketing materials, and direct support from our team.'],
                'content' => [
                    'id' => '<h2>Keuntungan</h2><ul><li>Diskon reseller bertingkat</li><li>Foto & video produk siap pakai</li><li>Terdaftar di halaman "Beli di Mana"</li></ul><h2>Syarat</h2><ul><li>Memiliki toko online atau offline yang aktif</li><li>Bersedia menjual sesuai harga eceran yang disarankan</li></ul>',
                    'en' => '<h2>Benefits</h2><ul><li>Tiered reseller discounts</li><li>Ready-to-use product photos & videos</li><li>Listed on our "Where to Buy" page</li></ul><h2>Requirements</h2><ul><li>An active online or offline store</li><li>Selling at the recommended retail price</li></ul>',
                ],
            ],
        ];

        foreach ($pages as $key => $values) {
            Page::query()->firstOrCreate(['key' => $key], $values);
        }
    }

    private function stores(): void
    {
        $stores = [
            ['Lumière Official Shop', StoreType::Marketplace, MarketplacePlatform::Shopee, 'https://shopee.co.id/', null, null],
            ['Lumière Official Store', StoreType::Marketplace, MarketplacePlatform::Tokopedia, 'https://www.tokopedia.com/', null, null],
            ['Lumière Beauty', StoreType::Marketplace, MarketplacePlatform::TiktokShop, 'https://shop.tiktok.com/', null, null],
            ['Lumière Studio Kemang', StoreType::Offline, null, null, 'Jakarta Selatan', "Jl. Kemang Raya No. 10\nJakarta Selatan"],
        ];

        foreach ($stores as $order => [$name, $type, $platform, $url, $city, $address]) {
            OfficialStore::query()->firstOrCreate(
                ['name' => $name],
                ['type' => $type, 'platform' => $platform, 'url' => $url, 'city' => $city, 'address' => $address, 'sort_order' => $order],
            );
        }
    }

    private function posts(): void
    {
        $post = Post::query()->firstOrCreate(
            ['slug' => 'urutan-skincare-pagi-untuk-pemula'],
            [
                'title' => ['id' => 'Urutan Skincare Pagi untuk Pemula', 'en' => 'A Beginner Morning Skincare Routine'],
                'excerpt' => ['id' => 'Empat langkah sederhana agar kulit terawat tanpa ribet.', 'en' => 'Four simple steps for cared-for skin without the fuss.'],
                'content' => [
                    'id' => '<h2>1. Pembersih</h2><p>Gunakan pembersih yang lembut agar skin barrier tetap terjaga.</p><h2>2. Serum</h2><p>Pilih bahan aktif sesuai kebutuhan, misalnya niacinamide untuk kulit kusam.</p><h2>3. Pelembap</h2><p>Kunci hidrasi dengan pelembap yang cocok untuk jenis kulitmu.</p><h2>4. Tabir surya</h2><p>Langkah paling penting di pagi hari. Pakai ulang setiap 2–3 jam saat beraktivitas di luar.</p>',
                    'en' => '<h2>1. Cleanser</h2><p>Use a gentle cleanser to keep the skin barrier intact.</p><h2>2. Serum</h2><p>Pick an active for your needs, such as niacinamide for dullness.</p><h2>3. Moisturizer</h2><p>Lock in hydration with a moisturizer for your skin type.</p><h2>4. Sunscreen</h2><p>The most important morning step. Reapply every 2–3 hours outdoors.</p>',
                ],
                'status' => ProductStatus::Published,
                'published_at' => now()->subDays(2),
            ],
        );

        $post->products()->syncWithoutDetaching(
            Product::query()->whereIn('slug', ['gentle-cleansing-milk', 'glow-renewal-serum', 'hydra-cloud-cream'])->pluck('id'),
        );
    }

    private function promotions(): void
    {
        if (Promotion::query()->exists()) {
            return;
        }

        Promotion::query()->create([
            'placement' => PromotionPlacement::TopBar,
            'title' => ['id' => 'Gratis ongkir se-Indonesia', 'en' => 'Free shipping across Indonesia'],
            'body' => ['id' => 'untuk pembelian via WhatsApp minggu ini.', 'en' => 'for WhatsApp orders this week.'],
            'link_url' => '/produk',
            'link_label' => ['id' => 'Belanja sekarang', 'en' => 'Shop now'],
            'ends_at' => now()->addWeek(),
        ]);
    }
}
