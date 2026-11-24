# AGENTS.md

Panduan untuk AI coding agent (dan developer) yang bekerja di repositori ini. Baca seluruh file ini sebelum mengubah kode.

---

## 1. Ringkasan Proyek

Website **produk kecantikan** yang interaktif, dengan animasi 3D berbasis **Three.js**. Seluruh konten produk dikelola lewat **CMS (panel admin Filament)**: admin bisa menambah, mengubah, dan menghapus produk, termasuk **foto** dan **desain produk** (model 3D, tekstur label, varian warna, preset animasi), tanpa menyentuh kode.

**Tujuan utama**
- Pengunjung bisa melihat produk dalam tampilan 3D yang bisa diputar, di-zoom, dan dianimasikan saat scroll.
- Admin non-teknis bisa mengelola katalog sepenuhnya dari panel admin.
- Situs tetap cepat, responsif di mobile, dan aksesibel, walau sarat animasi.

**Prinsip**
1. **Konten dari CMS, bukan hardcode.** Teks, gambar, model 3D, warna, dan pengaturan animasi produk semuanya berasal dari database lewat panel admin.
2. **Progressive enhancement.** Halaman dirender server sebagai HTML lengkap (Blade). 3D adalah lapisan tambahan: jika WebGL tidak tersedia, perangkat lemah, atau pengguna memilih `prefers-reduced-motion`, tampilkan foto produk statis yang tetap rapi.
3. **Performa itu fitur.** Ikuti budget performa di bagian 8.
4. **Estetika premium.** Nuansa lembut dan elegan, ruang kosong yang lega, tipografi yang rapi, dan gerakan halus, bukan berlebihan.
5. **Sederhana.** Satu aplikasi Laravel untuk website + CMS + database. Jangan menambah SPA, API terpisah, atau framework JS besar tanpa persetujuan pemilik proyek.

---

## 2. Tech Stack

> Jika pemilik proyek memutuskan stack lain, perbarui bagian ini terlebih dahulu sebelum menulis kode.

| Lapisan | Pilihan | Catatan |
|---|---|---|
| Bahasa | **PHP 8.4** (minimal 8.3) | Dijalankan lewat Docker. PHP 7.4 di laptop **tidak dipakai** untuk proyek ini |
| Framework | **Laravel 13** | Halaman publik dengan **Blade** |
| Panel CMS | **Filament 5** | Di `/admin` |
| Database | **MySQL 8.x** | Charset `utf8mb4` |
| Admin database | **phpMyAdmin** | `http://localhost:8080` (lihat bagian 5) |
| Media | **spatie/laravel-medialibrary** + `filament/spatie-laravel-media-library-plugin` | Konversi gambar otomatis (WebP, beberapa ukuran) |
| Multi-bahasa | **spatie/laravel-translatable** + field ID/EN buatan sendiri (`App\Filament\Support\Translatable`) | Bahasa `id` (default) dan `en`, lihat bagian 6.6 |
| 3D | **three.js** (vanilla, ES modules) | Tanpa React. Loader: `GLTFLoader` + `DRACOLoader` + `KTX2Loader` |
| Animasi | **GSAP** + ScrollTrigger | Satu-satunya library animasi scroll |
| Interaksi UI | **Alpine.js** | Ganti varian, menu, modal, dsb. |
| Styling | **Tailwind CSS 4** | Design token di CSS variables |
| Bundler | **Vite** (`laravel-vite-plugin`) | Build aset di lokal/CI, server production tidak butuh Node.js |
| Lingkungan dev | **Docker** via **Laravel Sail** + service phpMyAdmin | Lihat bagian 4 |
| Testing | **Pest** (PHP), **Vitest** (util JS) | |
| Code style | **Laravel Pint** (PHP), Prettier (JS/Blade opsional) | |

---

## 3. Struktur Folder

```
.
├── app/
│   ├── Enums/                      # RenderMode, ContainerShape, Material, AnimationPreset, ProductStatus
│   ├── Filament/
│   │   └── Resources/
│   │       ├── Products/           # ProductResource (form, table, pages)
│   │       └── Categories/
│   │   └── Pages/                  # ManageSiteSettings, ManageHomepage
│   ├── Http/Controllers/           # HomeController, ProductController
│   ├── Models/                     # Product, ProductVariant, Category, SiteSetting, Homepage
│   └── View/Components/            # Komponen Blade berbasis class (mis. ProductViewer)
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/                    # DemoProductSeeder untuk data contoh
├── resources/
│   ├── views/
│   │   ├── layouts/app.blade.php
│   │   ├── pages/                  # home, products/index, products/show
│   │   └── components/             # <x-product-viewer>, <x-product-card>, ...
│   ├── css/app.css
│   └── js/
│       ├── app.js                  # Alpine + inisialisasi ringan (tanpa three.js)
│       └── three/                  # SEMUA kode Three.js
│           ├── mount.js            # Cari [data-product-viewer], dynamic import viewer
│           ├── viewer.js           # Scene, kamera, renderer, controls, lifecycle
│           ├── product-model.js    # Load GLB
│           ├── procedural.js       # Kemasan procedural (bottle, jar, tube, ...)
│           ├── materials.js        # Preset material
│           ├── presets/            # rotate, float, scroll-reveal, explode
│           ├── capabilities.js     # Deteksi WebGL & perangkat lemah
│           └── dispose.js
├── public/
│   ├── draco/                      # Decoder Draco (salin dari three/examples/jsm/libs/draco)
│   └── basis/                      # Transcoder KTX2 (salin dari three/examples/jsm/libs/basis)
├── tests/
│   ├── Feature/
│   └── Unit/
├── compose.yaml                    # Laravel Sail + phpMyAdmin
└── AGENTS.md
```

Aturan:
- Semua kode Three.js **harus** berada di `resources/js/three/`.
- `three` dan `gsap` **tidak boleh** di-import di `app.js`. Muat lewat `import()` dinamis dari `mount.js`, hanya bila halaman memiliki elemen `[data-product-viewer]`.
- Logika bisnis di Model/Action/Service, bukan di Blade atau di Filament Resource yang gemuk.

---

## 4. Lingkungan Development (Docker)

Laptop memakai PHP 7.4, yang tidak didukung Laravel 13 dan Filament 5. **Semua perintah PHP, Composer, Artisan, dan npm dijalankan di dalam container** lewat Sail. Jangan menjalankan `php` atau `composer` langsung di laptop untuk proyek ini.

### 4.1 Prasyarat
- Docker Desktop (Windows: aktifkan **WSL2** dan simpan folder proyek di filesystem WSL, mis. `~/projects/...`, bukan di `C:\`, agar cepat).
- Lokasi di laptop pemilik proyek (disk C: sempit, semua di **D:**):
  - Program Docker Desktop: `D:\Docker\DockerDesktop`; data image/container: `D:\Docker\wsl`
  - Distro WSL `Ubuntu-24.04`: `D:\WSL\Ubuntu`
  - Proyek: `~/projects/beauty-cms` di dalam Ubuntu (fisiknya di virtual disk pada D:). Buka di VS Code lewat ekstensi WSL (`code .` dari terminal Ubuntu).
- Alias Sail: `alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'`

### 4.2 Service di `compose.yaml`
| Service | Image | Port lokal |
|---|---|---|
| `laravel.test` | Sail runtime PHP 8.4 | `80` (website), `5173` (Vite) |
| `mysql` | `mysql:8.4` | `3306` |
| `phpmyadmin` | `phpmyadmin:latest` | `8080` |

Tambahkan service phpMyAdmin secara manual (Sail tidak menyediakannya):
```yaml
phpmyadmin:
  image: 'phpmyadmin:latest'
  ports:
    - '${FORWARD_PMA_PORT:-8080}:80'
  environment:
    PMA_HOST: mysql
    PMA_PORT: 3306
    UPLOAD_LIMIT: 256M
  networks:
    - sail
  depends_on:
    - mysql
```

### 4.3 `.env` (lokal)
```
APP_URL=http://localhost
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=beauty_cms
DB_USERNAME=sail
DB_PASSWORD=password
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
```
Di dalam Docker, `DB_HOST` adalah nama service (`mysql`), **bukan** `127.0.0.1`.

### 4.4 Mode sementara tanpa Docker (aktif saat ini)
Atas permintaan pemilik proyek, sampai Windows direstart dan Docker/WSL2 siap, proyek berjalan **sementara** tanpa Docker:
- Proyek: `D:\projects\beauty-cms`
- PHP 8.4 portable: `D:\tools\php84\php.exe` (+ `D:\tools\composer.phar`). **Bukan** PHP 7.4 XAMPP.
- MySQL 8.4 portable: `D:\tools\mysql84`, data `D:\tools\mysql84-data`, **port 3307**, user `beauty`/`password`, database `beauty_cms` dan `beauty_cms_testing`. Port 3306 dipakai MariaDB XAMPP milik proyek lain; jangan diubah.
- Jalankan semua (MySQL, `artisan serve` di `http://127.0.0.1:8000`, queue worker): `D:\projects\beauty-cms\start-dev.cmd`.
- Pengganti perintah `sail ...` di mode ini: `D:\tools\php84\php.exe artisan ...`, `D:\tools\php84\php.exe D:\tools\composer.phar ...`, `npm ...`.
- Setelah Docker siap: pindahkan proyek ke Sail (bagian 4.1–4.3), lalu hapus bagian ini.

---

## 5. Database: MySQL + phpMyAdmin

- Charset/collation: `utf8mb4` / `utf8mb4_unicode_ci` (default Laravel).
- **Struktur database hanya diubah lewat migration Laravel** (`database/migrations`). Setiap perubahan skema = migration baru; jangan mengedit migration yang sudah dijalankan di production.
- Data contoh lewat seeder/factory, bukan input manual di phpMyAdmin.

**phpMyAdmin boleh dipakai untuk:** melihat isi tabel saat debugging, menjalankan `SELECT`, export (backup `.sql`) dan import (restore), serta mengelola user database.

**phpMyAdmin tidak boleh dipakai untuk:** mengubah struktur tabel (pakai migration), atau mengedit/menghapus konten produk dan media. Tabel `media` terhubung ke file fisik di storage, jadi menghapus baris di sana meninggalkan file yatim atau merusak tampilan. Edit konten lewat Filament.

**Backup:** export database (phpMyAdmin atau `mysqldump`) **dan** folder `storage/app/public` sebelum migration besar atau deploy.

**Production:** phpMyAdmin tidak boleh terbuka ke publik (batasi IP, password kuat, atau pakai bawaan panel hosting). Aplikasi memakai user database khusus, bukan `root`.

---

## 6. Model Konten

### 6.1 Tabel `products`
| Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | bigint | ✅ | |
| `category_id` | foreignId → `categories` | ✅ | |
| `name` | string | ✅ | |
| `slug` | string unik | ✅ | Otomatis dari `name`, untuk URL `/produk/{slug}` |
| `short_description` | string(160) | ✅ | Juga dipakai meta description |
| `description` | longText (HTML dari RichEditor) | | |
| `price` | unsignedInteger | | Dalam IDR |
| `ingredients` | json (array string) | | |
| `render_mode` | string (enum `RenderMode`) | ✅ | `model3d` / `procedural` / `image` |
| `container_shape` | string (enum) | | `bottle` / `jar` / `tube` / `dropper` / `compact` |
| `base_color` | string(7) | | Hex, divalidasi `^#[0-9a-fA-F]{6}$` |
| `material` | string (enum) | | `glossy` / `matte` / `frosted_glass` / `metallic` |
| `animation_preset` | string (enum) | | `rotate` / `float` / `scroll_reveal` / `explode` / `none` |
| `background_theme` | string | | Hex atau gradient CSS |
| `camera_position` | json `{x,y,z}` | | Opsional |
| `is_featured` | boolean | | |
| `status` | string (enum `ProductStatus`) | ✅ | `draft` / `published` |
| `published_at` | timestamp nullable | | |
| `meta_title`, `meta_description` | string nullable | | SEO |
| `bpom_number` | string(30) nullable | | Nomor notifikasi BPOM, disimpan huruf besar tanpa spasi (mis. `NA18230100123`) |
| `halal_number` | string(60) nullable | | Nomor sertifikat Halal BPJPH |
| `net_content` | string(30) nullable | | Isi bersih, mis. `30 ml` |
| `skin_types`, `skin_concerns`, `claims` | json (array nilai enum) | | `SkinType`, `SkinConcern`, `ProductClaim`; dipakai filter katalog |
| `key_ingredients` | json `[{name, concentration, benefit: {id, en}}]` | | Bahan unggulan (tidak lewat HasTranslations; `benefit` per bahasa) |
| `how_to_use`, `warnings` | json (translatable) | | |
| `marketplace_url` | string nullable | | Override tautan marketplace dari Pengaturan Situs |
| timestamps, softDeletes | | | |

Model `Product` memakai **cast ke Enum** untuk semua kolom enum dan scope `published()` (`status = published` dan `published_at <= now()`).

### 6.2 Koleksi media (Spatie Media Library) pada `Product`
| Koleksi | Isi | Aturan |
|---|---|---|
| `gallery` | Foto produk (multiple) | Wajib min. 1. Foto pertama = fallback utama. Konversi `thumb` (400px), `card` (800px), `full` (1600px), format WebP |
| `model` | File `.glb` (single) | Wajib jika `render_mode = model3d`. Validasi **ekstensi** `.glb` (MIME dari browser sering `application/octet-stream`) |
| `label_texture` | Gambar label kemasan (single) | Maks. 2048px, konversi WebP |
| `og_image` | Gambar share sosial media (single) | Opsional |

### 6.3 Tabel `product_variants`
`product_id`, `label`, `color` (hex), `sort_order`, plus koleksi media `label_texture` dan `image` (opsional). Dikelola di form produk lewat **Repeater** relasi.
Override opsional per varian (shade/ukuran): `price`, `net_content`, `bpom_number`, `marketplace_url`. Nilai kosong memakai nilai produk (`ProductVariant::pickerData()`); harga di kartu katalog tampil "Mulai Rp …" bila harga varian berbeda (`Product::formattedPriceRange()`).

### 6.4 Tabel lain
- **`categories`**: `name`, `slug`, `sort_order`, koleksi media `icon`.
- **Pengaturan situs** (`spatie/laravel-settings` atau tabel satu baris): nama brand, logo, palet warna, tautan sosial media, kontak. Halaman Filament `ManageSiteSettings`.
- **Beranda**: produk hero (relasi ke `products`), headline, sub-headline, CTA. Halaman Filament `ManageHomepage`.
- **`users`**: admin panel. Hanya user dengan izin `canAccessPanel()` yang bisa masuk `/admin`.

### 6.5 Form produk di Filament
Kelompokkan form dalam **Tabs**: *Info Produk* · *Foto* · *Desain 3D* · *Varian* · *SEO*. Di tab *Desain 3D*:
- Field `model` hanya tampil bila `render_mode = model3d`, `container_shape` hanya bila `procedural` (gunakan `->visible()` / `->live()`).
- `base_color` memakai `ColorPicker`.
- Sertakan teks bantuan tentang batas ukuran file (bagian 8).
- Field yang diterjemahkan tampil berdampingan ID/EN (bagian 6.6).

### 6.6 Multi-bahasa (ID/EN)
- Bahasa: `id` (default) dan `en`. URL: tanpa prefix untuk `id` (`/produk/{slug}`), prefix `/en` untuk `en` (`/en/products/{slug}`).
- Kolom yang diterjemahkan (tipe `json`, trait `HasTranslations`): `products.name`, `short_description`, `description`, `ingredients`, `how_to_use`, `warnings`, `meta_title`, `meta_description`; `product_variants.label`; `categories.name`; teks beranda (headline, sub-headline, CTA); serta kolom teks model konten di bagian 6.8.
- `slug` **tidak** diterjemahkan (satu slug untuk kedua bahasa).
- Bila terjemahan `en` kosong, tampilkan versi `id` (fallback locale).
- Setiap halaman publik memuat `<link rel="alternate" hreflang="...">` untuk `id`, `en`, dan `x-default`.
- Teks UI statis di `lang/id` dan `lang/en`.
- Admin: pakai `Translatable::make('name', label, fn ($path) => TextInput::make($path))` (state `name.id` / `name.en`). Halaman Edit memakai trait `FillsTranslations`; Repeater relasi memakai `mutateRelationshipDataBeforeFillUsing` + `Translatable::fill()`. Plugin locale-switcher (`lara-zeus/spatie-translatable`) **tidak** dipakai karena bermasalah dengan Repeater relasi & upload media.
- Link publik dibuat dengan `localized_route('products.show', [...])`; nama route EN berawalan `en.`.

### 6.7 Ruang lingkup
Situs adalah **katalog/showcase**, tanpa keranjang, checkout, atau pembayaran. Ajakan beli berupa tombol ke WhatsApp/marketplace dari pengaturan situs.

### 6.8 Konten & kotak masuk
| Model / tabel | Isi | Halaman publik (ID · EN) | Admin |
|---|---|---|---|
| `Testimonial` | Nama, keterangan, isi, rating, produk (opsional), media `photo`/`before`/`after` | Beranda (`is_featured`), halaman produk terkait | Konten → Testimoni |
| `Faq` | Pertanyaan/jawaban; `product_id` null = FAQ umum | `/faq` · `/en/faq` (404 bila kosong), halaman produk | Konten → FAQ (modal) |
| `Page` | Satu baris per `PageKey` (`about`, `privacy`, `terms`, `reseller`): judul, intro, isi rich text | `/tentang-kami`, `/kebijakan-privasi`, `/syarat-dan-ketentuan` (404 bila belum dibuat; tautan footer disembunyikan) | Konten → Halaman |
| `OfficialStore` | Toko resmi `StoreType` marketplace/offline | `/beli-di-mana` · `/en/where-to-buy` | Konten → Toko resmi |
| `Post` (+ pivot `post_product`) | Artikel blog, status/terbit seperti produk, produk terkait, media `cover` | `/artikel` · `/en/articles` | Konten → Artikel |
| `Promotion` | `PromotionPlacement` `top_bar` (bar pengumuman) / `home` (banner); jadwal `starts_at`/`ends_at` dicek per request | Semua halaman / beranda | Konten → Promo |
| `ContactMessage` | Form kontak | `/kontak` · `/en/contact` | Kotak Masuk → Pesan |
| `ResellerApplication` | Form reseller, `ApplicationStatus` | `/reseller` (deskripsi dari `Page` `reseller`) | Kotak Masuk → Reseller |
| `Subscriber` | Email newsletter (footer) | — | Kotak Masuk → Subscriber (unduh CSV) |

- Rute ID dan EN didefinisikan sekali di `routes/web.php` (`$publicRoutes`), hanya segmen path yang berbeda.
- Filter katalog (`App\Support\ProductFilters`): `?q=&category=&skin=&concern=&sort=`, dijalankan pada daftar produk ber-cache (katalog satu brand kecil); nilai tak dikenal diabaikan.
- Form publik: Form Request, honeypot `website` (`<x-honeypot />`), rate limiter `public-forms` (5/menit per IP). Pesan validasi Indonesia di `lang/id/validation.php`.
- Tracking: `ga4_measurement_id` dan `meta_pixel_id` di Pengaturan Situs; script hanya dicetak bila ID lolos pola ketat (`SiteSetting::ga4Id()`/`metaPixelId()`). Link dengan `data-track="whatsapp_click|marketplace_click"` dilaporkan oleh `app.js`.
- Data contoh: `DemoContentSeeder` (aman dijalankan ulang; detail produk hanya diisi bila kolomnya kosong).
- Dasbor admin (`App\Filament\Pages\Dashboard`, widget di `app/Filament/Widgets`): ringkasan bisnis & konten, grafik kotak masuk dan produk per kategori, pesan/reseller terbaru, serta checklist "Perlu perhatian" dari `App\Services\SiteHealth`. Tambah pemeriksaan baru di service tersebut (teks di `admin.dashboard.health.*`).

---

## 7. Pipeline 3D & Animasi

### 7.1 Dari Blade ke Three.js
Komponen `<x-product-viewer :product="$product" />` merender:
1. **Gambar fallback** (`<img>` dari konversi `card`/`full`, dengan `alt`). Selalu ada di HTML.
2. Elemen container dengan `data-product-viewer` dan konfigurasi JSON (dibuat di server lewat method `Product::viewerConfig()`):
```html
<div data-product-viewer
     data-config='@json($product->viewerConfig())'
     aria-label="Tampilan 3D {{ $product->name }}">
  <img src="..." alt="..." class="viewer-fallback">
</div>
```
`viewerConfig()` hanya berisi data yang dibutuhkan viewer: `renderMode`, `modelUrl`, `labelTextureUrl`, `labelText` (nama produk untuk label otomatis bila tidak ada gambar label), `containerShape`, `baseColor`, `material`, `animationPreset`, `backgroundTheme`, `camera`, `variants[]`. Jangan memasukkan data sensitif.

### 7.2 Alur render
```
mount.js → cek capabilities (WebGL, reduced motion, perangkat lemah)
  ├─ tidak mampu / renderMode = image → biarkan gambar fallback, selesai
  └─ mampu → IntersectionObserver: saat container mendekati viewport → import('./viewer.js')
        ├─ renderMode = model3d   → GLTFLoader (+ Draco/KTX2) memuat modelUrl
        └─ renderMode = procedural→ procedural.js membuat geometri + labelTexture + material
     → setelah frame pertama siap, fade-out gambar fallback
     → jika gagal load → log error, tetap tampilkan gambar fallback
```
- Ganti varian (Alpine) mengirim event `product:variant-change` → viewer mengganti warna/tekstur material **tanpa** me-reload model.
- Viewer harus punya method `destroy()` yang menghentikan loop render dan memanggil `dispose.js`.
- Jika memakai Livewire di halaman publik, bungkus container viewer dengan `wire:ignore`.

### 7.3 Preset animasi
Setiap file di `resources/js/three/presets/` mengekspor fungsi dengan kontrak yang sama:
```js
/**
 * @param {THREE.Object3D} target
 * @param {{ intensity?: number, enabled: boolean, camera: THREE.Camera, container: HTMLElement, controls?: OrbitControls, requestRender?: () => void }} options
 * @returns {{ update(delta: number): void, dispose(): void, isRunning?(): boolean, toggle?(): boolean }}
 */
export function createPreset(target, options) {}
```
- `isRunning()` = preset berbasis waktu yang membuat loop render tetap jalan; preset berbasis scroll memanggil `requestRender()`. `toggle()` (opsional) dipakai tombol jeda/putar.
- Daftarkan preset baru di `presets/index.js` (dimuat lewat `import()` dinamis).
- `rotate`: rotasi lambat sumbu Y, berhenti saat user berinteraksi.
- `float`: naik-turun halus.
- `scroll_reveal`: model/kamera bergerak mengikuti scroll (GSAP ScrollTrigger, `scrub`).
- `explode`: bagian model (tutup, badan) terpisah saat scroll. Hanya untuk GLB dengan node bernama.
- Animasi berbasis waktu wajib memakai `delta` (`THREE.Clock`), bukan nilai tetap per frame.
- Bila `prefers-reduced-motion`, preset otomatis tidak dijalankan; interaksi manual tetap boleh.

### 7.4 Interaksi
- `OrbitControls`: `enablePan = false`, batasi `minDistance`/`maxDistance` dan sudut polar, `enableDamping = true`.
- Mobile: drag untuk memutar tanpa membajak scroll halaman (atur `touch-action`).
- Zoom lewat tombol +/− (bukan scroll mouse), karena zoom wheel membajak scroll halaman. `enableZoom = false`.
- Tombol kontrol (putar, reset view, ganti varian) bisa diakses keyboard dan punya `aria-label`.

---

## 8. Budget Performa

| Item | Batas |
|---|---|
| Ukuran file `.glb` | ≤ **5 MB** (target ≤ 2 MB), kompresi **Draco** atau **Meshopt** |
| Tekstur | ≤ **2048 × 2048**, utamakan **KTX2** / WebP |
| Jumlah segitiga per produk | ≤ ~100k |
| Renderer WebGL aktif per halaman | Maks. **1** |
| Pixel ratio | `renderer.setPixelRatio(Math.min(devicePixelRatio, 2))` |
| LCP (halaman produk, 4G mobile) | < 2,5 s (LCP harus gambar fallback, bukan canvas) |
| JS awal (`app.js`) | Tanpa three.js/gsap; target < 50 KB gzip |

Aturan:
- Render loop berhenti saat tidak ada animasi/interaksi dan saat canvas di luar viewport.
- Dispose geometri, material, tekstur, dan renderer saat viewer dihancurkan.
- Preload model hanya untuk produk hero di beranda.
- Perangkat lemah (`capabilities.js`): matikan shadow & efek tambahan, atau pakai mode gambar.
- Query: selalu eager load (`with(['category', 'media', 'variants.media'])`) untuk mencegah N+1. Aktifkan `Model::preventLazyLoading()` di non-production.
- Cache query katalog & pengaturan situs (`Cache::remember`) dan hapus cache lewat observer saat produk/pengaturan disimpan. Implementasi: `App\Services\Catalog` (kunci cache berversi, `Catalog::flush()` dari `CatalogCacheObserver` dan event `Media`). Laravel 13 hanya meng-unserialize kelas yang terdaftar di `config/cache.php` → `serializable_classes`; **tambahkan kelas baru ke daftar itu** bila ikut di-cache.
- Konversi gambar Spatie dijalankan lewat **queue**.
- Production: `php artisan optimize`, OPcache aktif, aset dari `npm run build` (dengan hash, cache browser panjang).

---

## 9. Aksesibilitas & SEO

- Konten penting (nama, harga, deskripsi, kandungan) ada di HTML Blade, **tidak** hanya di dalam canvas.
- Setiap viewer punya `aria-label` dan gambar fallback dengan `alt` yang deskriptif.
- Kontras teks minimal WCAG AA; seluruh kontrol bisa dipakai lewat keyboard.
- Halaman produk: `<title>`, meta description, Open Graph (dari `og_image` atau foto pertama), canonical URL, dan JSON-LD `Product` (`AggregateOffer` bila harga varian berbeda). Artikel: JSON-LD `Article`.
- Halaman katalog yang difilter atau halaman ke-2 dst. memakai `noindex, follow`.
- Sitemap (`spatie/laravel-sitemap`) berisi produk dan artikel berstatus published, halaman `Page` yang sudah dibuat, serta halaman statis.

---

## 10. Perintah

```bash
# Pertama kali (tanpa PHP di laptop): buat proyek via container
#   lihat dokumentasi Laravel Sail → "Installing Sail into existing applications" / laravel.build
sail up -d                         # jalankan semua container
sail down                          # matikan container

sail composer install
sail artisan key:generate
sail artisan migrate --seed        # migrasi + data contoh
sail artisan storage:link
sail artisan make:filament-user    # buat akun admin

sail npm install
sail npm run dev                   # Vite dev server (HMR)
sail npm run build                 # build aset production

sail artisan queue:work            # proses konversi gambar
sail artisan test                  # Pest
sail npm run test                  # Vitest (util JS)
sail pint                          # format kode PHP

sail artisan make:migration ...    # perubahan skema
```

Akses lokal:
- Website: `http://localhost`
- Panel admin: `http://localhost/admin`
- phpMyAdmin: `http://localhost:8080`

---

## 11. Konvensi Kode

- PHP: ikuti Laravel Pint (preset `laravel`), `declare(strict_types=1);` di file baru, type hint di parameter & return.
- Nilai tetap (render mode, material, dsb.) memakai **PHP Enum** di `app/Enums`, dipakai juga untuk opsi `Select` di Filament.
- Validasi upload & field di Filament form (`->rules()`, `->acceptedFileTypes()`, `->maxSize()`).
- Controller tipis: ambil data, panggil view. Tidak ada query di Blade.
- JS: ES modules, tanpa jQuery. Satu tanggung jawab per file di `resources/js/three/`.
- Nama tabel/kolom: `snake_case`. Nama class: `PascalCase`. Nama file JS: `kebab-case`.
- Teks UI dalam **Bahasa Indonesia**; kode dan komentar kode dalam **Bahasa Inggris**. Gunakan file terjemahan `lang/id` untuk teks UI berulang.
- Commit message: Conventional Commits (`feat:`, `fix:`, `refactor:`, `chore:`, `docs:`).

---

## 12. Testing

- **Feature (Pest):**
  - Halaman katalog hanya menampilkan produk `published`.
  - Produk `draft` mengembalikan 404 di halaman publik.
  - Halaman detail berisi gambar fallback, `data-config` yang valid, dan meta SEO.
  - `/admin` menolak tamu; admin bisa membuat produk dengan foto (Filament testing helpers / Livewire test).
  - Validasi: upload selain `.glb` di field model ditolak; `base_color` non-hex ditolak.
- **Unit (Pest):** `Product::viewerConfig()`, scope `published()`, enum.
- **Unit JS (Vitest):** `capabilities.js`, parsing config, pemilihan render mode.
- Test memakai database terpisah (`beauty_cms_testing`) dengan `RefreshDatabase`, **bukan** database development.
- Sebelum menyatakan selesai: `sail pint --test && sail artisan test && sail npm run build`.

---

## 13. Aturan untuk Agent

**Lakukan**
- Jalankan semua perintah PHP/Composer/Artisan/npm lewat `sail`.
- Baca model, migration, dan Filament Resource terkait sebelum mengubah apa pun.
- Buat perubahan kecil dan terfokus; satu tugas = satu tujuan jelas.
- Tambah/ubah test untuk setiap perilaku baru.
- Sediakan fallback gambar untuk setiap fitur 3D baru.
- Perbarui AGENTS.md bila stack, struktur, atau konvensi berubah.

**Jangan**
- Jangan memakai PHP 7.4 lokal atau menurunkan versi Laravel/Filament agar cocok dengan PHP 7.4.
- Jangan hardcode data produk di Blade atau JS.
- Jangan mengubah struktur database lewat phpMyAdmin; gunakan migration.
- Jangan menjalankan `migrate:fresh`, `migrate:reset`, `db:wipe`, `DROP`, `TRUNCATE`, atau import `.sql` tanpa konfirmasi pemilik proyek dan backup terbaru.
- Jangan meng-import `three`/`gsap` di bundle utama.
- Jangan menambah React/Vue/SPA atau library 3D lain tanpa persetujuan.
- Jangan commit `.env`, dump `.sql`, isi `storage/app/public`, atau file model/foto besar.
- Jangan menonaktifkan validasi, Pint, atau test untuk "membuat build lolos".

---

## 14. Keamanan

- Upload: batasi tipe & ukuran (gambar: jpg/png/webp/avif ≤ 5 MB; model: `.glb` ≤ 5 MB). Simpan di disk `public` (lokal) atau S3/R2 (production), jangan pernah mengeksekusi file upload.
- Panel admin hanya untuk user dengan `canAccessPanel()` = true; aktifkan rate limiting login.
- Rich text dari admin disanitasi sebelum dirender (`{!! !!}` hanya untuk HTML yang sudah disanitasi).
- `APP_DEBUG=false` di production. User database khusus aplikasi, bukan `root`.
- phpMyAdmin production tidak terbuka ke publik (bagian 5).
- Header keamanan dasar (HSTS, `X-Content-Type-Options`, CSP yang mengizinkan `blob:` & `data:` untuk tekstur dan worker decoder).

---

## 15. Deploy (Production)

- Server butuh **PHP 8.3+** (disarankan 8.4) dengan ekstensi standar Laravel + `gd`/`imagick` + `exif`, serta **MySQL 8.x** dan akses SSH/Composer. Banyak hosting (termasuk shared hosting dengan cPanel/phpMyAdmin) bisa memilih versi PHP; pastikan disetel ke 8.3+.
- Node.js **tidak** dibutuhkan di server: jalankan `npm run build` di lokal/CI dan unggah folder `public/build`.
- Langkah: `composer install --no-dev --optimize-autoloader` → `php artisan migrate --force` → `php artisan storage:link` → `php artisan optimize` → `php artisan filament:optimize`.
- Queue: worker via Supervisor (VPS) atau cron `schedule:run` yang menjalankan `queue:work --stop-when-empty` (shared hosting).
- Document root diarahkan ke folder `public/`.

---

## 16. Definition of Done

Sebuah tugas dianggap selesai bila:
1. `sail pint --test`, `sail artisan test`, dan `sail npm run build` lulus.
2. Fitur berjalan di desktop & mobile (cek viewport 375 px dan 1440 px).
3. Fallback gambar dan `prefers-reduced-motion` sudah diperiksa.
4. Budget performa (bagian 8) tidak dilanggar.
5. Konten baru bisa dikelola dari panel Filament tanpa mengubah kode.
6. Perubahan skema dibuat sebagai migration baru.
7. Dokumentasi (termasuk file ini) diperbarui jika perlu.

---

## 17. Keputusan yang Masih Terbuka

Konfirmasi ke pemilik proyek sebelum mengimplementasikan:
- [x] E-commerce? **Tidak**, hanya katalog/showcase (bagian 6.7).
- [x] Desain produk? **Keduanya**: procedural + label (default, dipakai data demo) dan upload `.glb` opsional.
- [x] Multi-bahasa? **Ya, ID + EN** (bagian 6.6).
- [ ] Hosting target: shared hosting (cPanel) atau VPS? Penyimpanan media lokal atau S3/R2?
- [x] OS laptop development? **Windows 11 + WSL2**, Docker & WSL disimpan di disk D: (bagian 4.1).
- [ ] Panduan brand (logo, palet warna, font) sudah tersedia?

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
