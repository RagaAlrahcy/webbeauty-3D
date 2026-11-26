# Beauty CMS: Website Katalog Produk Kecantikan 3D

Website katalog untuk brand **skincare & makeup**. Produk ditampilkan dalam **tampilan 3D interaktif** (Three.js) dan seluruh konten dikelola lewat **panel admin (CMS)**.

Situs ini adalah **katalog**, bukan toko online: tidak ada keranjang atau pembayaran. Pengunjung diarahkan ke **WhatsApp** atau **marketplace/toko resmi** untuk membeli.

> Panduan teknis lengkap (arsitektur, pipeline 3D, budget performa, konvensi kode) ada di [AGENTS.md](AGENTS.md).

---

## Daftar Isi

- [Fitur](#fitur)
- [Tech Stack](#tech-stack)
- [Menjalankan di Lokal](#menjalankan-di-lokal)
- [Halaman & URL](#halaman--url)
- [Perintah Sehari-hari](#perintah-sehari-hari)
- [Testing & Kualitas Kode](#testing--kualitas-kode)
- [Struktur Folder](#struktur-folder)
- [Deploy ke Production](#deploy-ke-production)
- [Troubleshooting](#troubleshooting)

---

## Fitur

### Website publik
- **Tampilan produk 3D**: bisa diputar dan di-zoom, dengan preset animasi (rotate, float, scroll-reveal, explode). Kemasan bisa dibuat **procedural** (botol, jar, tube, dll. + label otomatis) atau dari file **`.glb`**. Kalau perangkat tidak mendukung WebGL atau pengguna memilih *reduced motion*, yang ditampilkan adalah foto produk.
- **Katalog** dengan pencarian, filter kategori, jenis kulit, masalah kulit, urutan harga/terbaru, dan paginasi.
- **Detail produk khas kecantikan**: nomor BPOM & sertifikat halal (dengan tautan cek resmi), isi bersih, jenis & masalah kulit, klaim (vegan, non-comedogenic, dll.), kandungan utama, cara pakai, peringatan, varian (warna/ukuran/harga), testimoni dan FAQ produk.
- **Artikel/blog** dengan produk terkait.
- **Halaman konten**: Tentang Kami, FAQ, Beli di Mana (toko resmi online & offline), Kebijakan Privasi, Syarat & Ketentuan.
- **Form**: Kontak, Pendaftaran Reseller, Newsletter. Semua dilindungi honeypot anti-spam dan rate limit.
- **Promo**: bar pengumuman di atas halaman dan banner beranda, terjadwal otomatis.
- **Dua bahasa**: Indonesia (default, tanpa prefix) dan Inggris (`/en/...`).
- **SEO**: meta & Open Graph, canonical, `hreflang`, JSON-LD (`Product`, `Article`), `sitemap.xml`, `noindex` untuk halaman katalog yang difilter.
- **Tracking opsional**: Google Analytics 4 dan Meta Pixel, termasuk event klik WhatsApp/marketplace.
- **Favicon** bawaan, atau unggah sendiri dari Pengaturan Situs.

### Panel admin (`/admin`, Filament)
- **Dasbor**: ringkasan bisnis & konten, grafik kotak masuk dan produk per kategori, pesan & pendaftar reseller terbaru, serta checklist **"Perlu perhatian"** (mis. produk tanpa BPOM, nomor WhatsApp kosong, promo yang hampir berakhir).
- **Katalog**: Produk (foto, desain 3D, varian, detail kecantikan) dan Kategori.
- **Konten**: Testimoni, FAQ, Halaman, Artikel, Promo, Toko resmi.
- **Kotak Masuk**: Pesan kontak (tandai dibaca, balas via WhatsApp atau email), Pendaftaran reseller (status & catatan), Subscriber newsletter (unduh CSV).
- **Situs**: Pengaturan Situs (nama brand, logo, favicon, warna, kontak, WhatsApp, tracking) dan Beranda.

---

## Tech Stack

| Lapisan | Teknologi |
|---|---|
| Bahasa & framework | PHP **8.4**, Laravel **13** |
| Panel CMS | Filament **5** (Livewire) |
| Database | MySQL **8.x** (`utf8mb4`) |
| Media | spatie/laravel-medialibrary (konversi WebP otomatis lewat queue) |
| Multi-bahasa | spatie/laravel-translatable (ID/EN) |
| SEO | spatie/laravel-sitemap |
| Frontend | Blade, Tailwind CSS **4**, Alpine.js, Vite |
| 3D & animasi | three.js, GSAP + ScrollTrigger (dimuat dinamis hanya di halaman yang butuh) |
| Testing | Pest (PHP), Vitest (JS), Laravel Pint (format kode) |

---

## Menjalankan di Lokal

### Prasyarat
- **PHP 8.4** dengan ekstensi `gd`, `exif`, `fileinfo`, `intl`, `mbstring`, `pdo_mysql`, `zip`
- **Composer 2**
- **MySQL 8.x**
- **Node.js 20+** dan npm

### Setup pertama kali

```bash
# 1. Clone repo
git clone https://github.com/RagaAlrahcy/webbeauty-3D.git beauty-cms
cd beauty-cms

# 2. Dependency
composer install
npm install

# 3. Konfigurasi
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

Buka `.env` dan sesuaikan koneksi database dengan MySQL Anda:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beauty_cms
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `beauty_cms` (charset `utf8mb4`), lalu:

```bash
# 4. Tabel + data contoh + akun admin
php artisan migrate --seed

# 5. Link folder upload ke public/
php artisan storage:link

# 6. Build aset frontend
npm run build
```

### Menjalankan aplikasi

Butuh **dua proses** yang berjalan bersamaan:

```bash
php artisan serve             # website di http://127.0.0.1:8000
php artisan queue:work        # memproses foto upload (konversi WebP & ukuran)
```

Saat mengedit tampilan (CSS/JS/Blade), jalankan juga `npm run dev` di terminal terpisah supaya perubahan langsung terlihat.

### Akses

| | URL |
|---|---|
| Website (Indonesia) | http://127.0.0.1:8000 |
| Website (English) | http://127.0.0.1:8000/en |
| Panel admin | http://127.0.0.1:8000/admin |

Akun admin dari seeder: **`admin@beauty.test`** / **`password`**

> ⚠️ Akun ini hanya untuk development. Ganti email & password sebelum dipakai di server sungguhan.

### Windows tanpa Docker (laptop pengembang utama)

Di laptop pengembang utama, PHP 8.4, Composer dan MySQL 8.4 dipasang **portable** di disk `D:` (bukan PHP 7.4 milik XAMPP):

| Komponen | Lokasi |
|---|---|
| PHP 8.4 | `D:\tools\php84\php.exe` |
| Composer | `D:\tools\composer.phar` |
| MySQL 8.4 | `D:\tools\mysql84` (data di `D:\tools\mysql84-data`), **port 3307**, user `beauty` / `password` |

Ada dua skrip sekali klik di root proyek:

| Skrip | Fungsi |
|---|---|
| [`start-dev.cmd`](start-dev.cmd) | Menyalakan MySQL (bila belum jalan), queue worker dan web server, lalu membuka browser |
| [`stop-dev.cmd`](stop-dev.cmd) | Mematikan semuanya dengan aman: queue worker selesai dulu, MySQL di-shutdown normal. MySQL/PHP XAMPP tidak disentuh |

Di mode ini, ganti `php` pada perintah-perintah di README dengan `D:\tools\php84\php.exe`, dan `composer` dengan `D:\tools\php84\php.exe D:\tools\composer.phar`.

---

## Halaman & URL

| Halaman | Indonesia | English |
|---|---|---|
| Beranda | `/` | `/en` |
| Katalog | `/produk` | `/en/products` |
| Detail produk | `/produk/{slug}` | `/en/products/{slug}` |
| Artikel | `/artikel` | `/en/articles` |
| Tentang Kami | `/tentang-kami` | `/en/about` |
| FAQ | `/faq` | `/en/faq` |
| Beli di Mana | `/beli-di-mana` | `/en/where-to-buy` |
| Kontak | `/kontak` | `/en/contact` |
| Reseller | `/reseller` | `/en/reseller` |
| Kebijakan Privasi | `/kebijakan-privasi` | `/en/privacy-policy` |
| Syarat & Ketentuan | `/syarat-dan-ketentuan` | `/en/terms` |
| Sitemap | `/sitemap.xml` | |

Halaman Tentang Kami, Kebijakan Privasi, Syarat & Ketentuan dan FAQ baru muncul (beserta tautannya di menu/footer) setelah isinya dibuat di admin.

---

## Perintah Sehari-hari

```bash
php artisan migrate                              # jalankan migration baru (setelah git pull)
php artisan db:seed --class=DemoContentSeeder    # isi ulang konten contoh (aman diulang, tidak menimpa data)
php artisan cache:clear                          # hapus cache katalog & pengaturan
npm run build                                    # build ulang aset setelah mengubah CSS/JS
npm run copy-decoders                            # salin decoder Draco/KTX2 setelah upgrade three.js
```

Setelah `git pull`, jalankan: `composer install`, `php artisan migrate`, `npm install`, `npm run build`.

---

## Testing & Kualitas Kode

```bash
php artisan test              # Pest: feature & unit test PHP
npm run test                  # Vitest: unit test JavaScript (deteksi perangkat, config viewer)
vendor/bin/pint               # format kode PHP (Laravel Pint)
```

Test memakai database terpisah **`beauty_cms_testing`** (lihat `phpunit.xml`), jadi data development tidak tersentuh. Buat database tersebut sekali sebelum menjalankan test pertama kali.

Sebelum commit, pastikan `vendor/bin/pint`, `php artisan test` dan `npm run build` lulus.

---

## Struktur Folder

```
app/
├── Enums/                  # Nilai tetap: jenis kulit, klaim, status, render mode, dll.
├── Filament/
│   ├── Pages/              # Dasbor, Pengaturan Situs, Beranda
│   ├── Resources/          # CRUD admin: produk, konten, kotak masuk
│   └── Widgets/            # Widget dasbor
├── Http/Controllers/       # Halaman publik (tipis)
├── Models/
├── Services/               # Catalog (query ber-cache), SiteHealth (checklist dasbor)
└── Support/                # ProductFilters, Locales, helper
database/
├── migrations/
├── factories/
└── seeders/                # DemoProductSeeder, DemoContentSeeder
lang/{id,en}/               # Teks UI website (site.php) & admin (admin.php)
resources/
├── views/                  # Blade: layouts, pages, components
├── css/app.css
└── js/
    ├── app.js              # Alpine + tracking (tanpa three.js)
    └── three/              # Semua kode 3D (viewer, procedural, preset animasi)
public/
├── draco/, basis/          # Decoder model 3D terkompresi
└── favicon.*               # Ikon bawaan
tests/                      # Pest (Feature, Unit) + Vitest
```

---

## Deploy ke Production

1. Server: **PHP 8.3+** (disarankan 8.4) dengan `gd`/`imagick` + `exif`, **MySQL 8.x**, akses SSH & Composer. Document root diarahkan ke folder `public/`.
2. Build aset di lokal/CI (`npm run build`) lalu unggah folder `public/build`. Node.js tidak dibutuhkan di server.
3. Di server:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   php artisan filament:optimize
   ```
4. Jalankan **queue worker** (Supervisor di VPS, atau cron `queue:work --stop-when-empty` di shared hosting). Tanpa worker, foto produk yang diunggah tidak diproses.
5. `.env` production: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain, dan user database khusus (bukan `root`).
6. Setelah online: lengkapi **Pengaturan Situs** (WhatsApp, logo, favicon, GA4/Pixel) dan cek checklist **"Perlu perhatian"** di dasbor.

---

## Troubleshooting

| Gejala | Solusi |
|---|---|
| Halaman tampil polos tanpa desain / error *"Vite manifest not found"* | Jalankan `npm run build` (atau `npm run dev` saat mengedit tampilan) |
| Halaman polos padahal `npm run dev` sudah dimatikan | Hapus file `public/hot`, lalu `npm run build` |
| Foto yang baru diunggah tidak muncul / buram | Pastikan `php artisan queue:work` sedang berjalan |
| Perubahan dari admin belum tampil di situs | `php artisan cache:clear` |
| Error koneksi database | Pastikan MySQL menyala dan `DB_*` di `.env` benar |
| Error sintaks PHP / versi tidak didukung | Pastikan memakai PHP 8.4 (`php -v`), bukan versi lama bawaan XAMPP |
| Favicon tidak berubah | Browser menyimpan cache favicon: tekan Ctrl+F5 |
