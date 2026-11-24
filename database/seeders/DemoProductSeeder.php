<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnimationPreset;
use App\Enums\ContainerShape;
use App\Enums\Material;
use App\Enums\ProductStatus;
use App\Enums\RenderMode;
use App\Models\Category;
use App\Models\Homepage;
use App\Models\Product;
use Database\Seeders\Support\PlaceholderImage;
use Illuminate\Database\Seeder;

/** Example catalog content (procedural packaging + generated photos). */
class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            'serum' => ['id' => 'Serum', 'en' => 'Serums'],
            'pelembap' => ['id' => 'Pelembap', 'en' => 'Moisturizers'],
            'bibir' => ['id' => 'Perawatan Bibir', 'en' => 'Lip Care'],
            'wajah' => ['id' => 'Riasan Wajah', 'en' => 'Face Makeup'],
        ])->map(fn (array $name, string $slug): Category => Category::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'sort_order' => array_search($slug, ['serum', 'pelembap', 'bibir', 'wajah'], true)],
        ));

        foreach ($this->products() as $index => $data) {
            if (Product::withTrashed()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $variants = $data['variants'] ?? [];
            unset($data['variants']);

            $product = Product::query()->create([
                ...$data,
                'category_id' => $categories[$data['category_id']]->id,
                'render_mode' => RenderMode::Procedural,
                'status' => ProductStatus::Published,
                'published_at' => now()->subDays(10 - $index),
            ]);

            $product->addMedia(PlaceholderImage::packaging($data['container_shape']->value, $data['base_color']))
                ->usingFileName($product->slug.'.png')
                ->withCustomProperties(['alt' => $data['name']['id']])
                ->toMediaCollection('gallery');

            foreach ($variants as $order => [$label, $color]) {
                $product->variants()->create(['label' => $label, 'color' => $color, 'sort_order' => $order]);
            }
        }

        $homepage = Homepage::current();
        $homepage->fill([
            'hero_product_id' => Product::query()->where('slug', 'glow-renewal-serum')->value('id'),
            'headline' => [
                'id' => 'Kulit bercahaya, dirawat dengan lembut.',
                'en' => 'Radiant skin, gently cared for.',
            ],
            'subheadline' => [
                'id' => 'Putar dan lihat setiap detail kemasan dalam 3D. Formula ringan dengan bahan pilihan untuk rutinitas harianmu.',
                'en' => 'Spin and explore every packaging detail in 3D. Lightweight formulas with carefully chosen ingredients for your daily ritual.',
            ],
            'cta_label' => ['id' => 'Jelajahi koleksi', 'en' => 'Explore the collection'],
            'cta_url' => '/produk',
        ])->save();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'slug' => 'glow-renewal-serum',
                'category_id' => 'serum',
                'name' => ['id' => 'Glow Renewal Serum', 'en' => 'Glow Renewal Serum'],
                'short_description' => [
                    'id' => 'Serum niacinamide 10% untuk kulit tampak cerah dan pori tersamarkan.',
                    'en' => '10% niacinamide serum for brighter-looking skin and refined pores.',
                ],
                'description' => [
                    'id' => '<p>Serum ringan yang cepat meresap, diformulasikan untuk membantu meratakan warna kulit.</p><ul><li>Tekstur cair, tidak lengket</li><li>Cocok untuk semua jenis kulit</li></ul>',
                    'en' => '<p>A lightweight, fast-absorbing serum formulated to help even out skin tone.</p><ul><li>Fluid, non-sticky texture</li><li>Suitable for all skin types</li></ul>',
                ],
                'ingredients' => [
                    'id' => ['Niacinamide 10%', 'Zinc PCA', 'Ekstrak Teh Hijau', 'Asam Hialuronat'],
                    'en' => ['Niacinamide 10%', 'Zinc PCA', 'Green Tea Extract', 'Hyaluronic Acid'],
                ],
                'price' => 149000,
                'container_shape' => ContainerShape::Dropper,
                'base_color' => '#e8b4a6',
                'material' => Material::FrostedGlass,
                'animation_preset' => AnimationPreset::Rotate,
                'background_theme' => 'radial-gradient(120% 90% at 50% 15%, #ffffff 0%, #f6d8d2 100%)',
                'is_featured' => true,
                'variants' => [
                    [['id' => 'Rose', 'en' => 'Rose'], '#e8b4a6'],
                    [['id' => 'Amber', 'en' => 'Amber'], '#d9a066'],
                    [['id' => 'Lavender', 'en' => 'Lavender'], '#c3b4e0'],
                ],
            ],
            [
                'slug' => 'hydra-cloud-cream',
                'category_id' => 'pelembap',
                'name' => ['id' => 'Hydra Cloud Cream', 'en' => 'Hydra Cloud Cream'],
                'short_description' => [
                    'id' => 'Pelembap gel-krim dengan ceramide untuk hidrasi hingga 48 jam.',
                    'en' => 'Gel-cream moisturizer with ceramides for up to 48 hours of hydration.',
                ],
                'description' => [
                    'id' => '<p>Tekstur seringan awan yang menjaga kelembapan dan memperkuat skin barrier.</p>',
                    'en' => '<p>A cloud-light texture that locks in moisture and supports the skin barrier.</p>',
                ],
                'ingredients' => [
                    'id' => ['Ceramide NP', 'Panthenol', 'Squalane', 'Centella Asiatica'],
                    'en' => ['Ceramide NP', 'Panthenol', 'Squalane', 'Centella Asiatica'],
                ],
                'price' => 189000,
                'container_shape' => ContainerShape::Jar,
                'base_color' => '#f3ece6',
                'material' => Material::Matte,
                'animation_preset' => AnimationPreset::Float,
                'is_featured' => true,
            ],
            [
                'slug' => 'velvet-lip-balm',
                'category_id' => 'bibir',
                'name' => ['id' => 'Velvet Lip Balm', 'en' => 'Velvet Lip Balm'],
                'short_description' => [
                    'id' => 'Lip balm bertekstur lembut dengan shea butter dan rona natural.',
                    'en' => 'Soft-touch lip balm with shea butter and a natural tint.',
                ],
                'ingredients' => [
                    'id' => ['Shea Butter', 'Minyak Jojoba', 'Vitamin E'],
                    'en' => ['Shea Butter', 'Jojoba Oil', 'Vitamin E'],
                ],
                'price' => 79000,
                'container_shape' => ContainerShape::Tube,
                'base_color' => '#c9737f',
                'material' => Material::Glossy,
                'animation_preset' => AnimationPreset::ScrollReveal,
                'is_featured' => true,
                'variants' => [
                    [['id' => 'Mawar', 'en' => 'Rosewood'], '#c9737f'],
                    [['id' => 'Peach', 'en' => 'Peach'], '#f0a98b'],
                    [['id' => 'Berry', 'en' => 'Berry'], '#8e3b55'],
                ],
            ],
            [
                'slug' => 'gentle-cleansing-milk',
                'category_id' => 'pelembap',
                'name' => ['id' => 'Gentle Cleansing Milk', 'en' => 'Gentle Cleansing Milk'],
                'short_description' => [
                    'id' => 'Susu pembersih yang mengangkat makeup tanpa membuat kulit kering.',
                    'en' => 'Cleansing milk that lifts makeup without drying the skin.',
                ],
                'ingredients' => [
                    'id' => ['Oat Kernel', 'Gliserin', 'Allantoin'],
                    'en' => ['Oat Kernel', 'Glycerin', 'Allantoin'],
                ],
                'price' => 119000,
                'container_shape' => ContainerShape::Bottle,
                'base_color' => '#f6e7dc',
                'material' => Material::Glossy,
                'animation_preset' => AnimationPreset::Rotate,
                'is_featured' => true,
            ],
            [
                'slug' => 'silk-finish-cushion',
                'category_id' => 'wajah',
                'name' => ['id' => 'Silk Finish Cushion', 'en' => 'Silk Finish Cushion'],
                'short_description' => [
                    'id' => 'Cushion ringan SPF 35 dengan hasil akhir satin yang natural.',
                    'en' => 'Lightweight SPF 35 cushion with a natural satin finish.',
                ],
                'ingredients' => [
                    'id' => ['Titanium Dioxide', 'Niacinamide', 'Ekstrak Mutiara'],
                    'en' => ['Titanium Dioxide', 'Niacinamide', 'Pearl Extract'],
                ],
                'price' => 229000,
                'container_shape' => ContainerShape::Compact,
                'base_color' => '#d8b9a6',
                'material' => Material::Metallic,
                'animation_preset' => AnimationPreset::Rotate,
                'is_featured' => true,
                'variants' => [
                    [['id' => '01 Ivory', 'en' => '01 Ivory'], '#f1dcc8'],
                    [['id' => '02 Natural', 'en' => '02 Natural'], '#d8b9a6'],
                    [['id' => '03 Sand', 'en' => '03 Sand'], '#c49a7c'],
                ],
            ],
            [
                'slug' => 'midnight-repair-oil',
                'category_id' => 'serum',
                'name' => ['id' => 'Midnight Repair Oil', 'en' => 'Midnight Repair Oil'],
                'short_description' => [
                    'id' => 'Minyak wajah malam dengan bakuchiol untuk kulit tampak kenyal.',
                    'en' => 'Overnight face oil with bakuchiol for plumper-looking skin.',
                ],
                'ingredients' => [
                    'id' => ['Bakuchiol', 'Minyak Rosehip', 'Squalane'],
                    'en' => ['Bakuchiol', 'Rosehip Oil', 'Squalane'],
                ],
                'price' => 209000,
                'container_shape' => ContainerShape::Bottle,
                'base_color' => '#4b3f63',
                'material' => Material::FrostedGlass,
                'animation_preset' => AnimationPreset::Float,
                'is_featured' => false,
            ],
        ];
    }
}
