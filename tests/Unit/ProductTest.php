<?php

use App\Enums\AnimationPreset;
use App\Enums\ContainerShape;
use App\Enums\RenderMode;
use App\Models\Product;
use App\Models\ProductVariant;

it('only returns published products whose publish date has passed', function () {
    $published = Product::factory()->create();
    Product::factory()->draft()->create();
    Product::factory()->scheduled()->create();

    expect(Product::query()->published()->pluck('id')->all())->toBe([$published->id]);
});

it('builds the viewer config without sensitive data', function () {
    $product = Product::factory()->create(['base_color' => '#112233']);
    ProductVariant::query()->create([
        'product_id' => $product->id,
        'label' => ['id' => 'Merah', 'en' => 'Red'],
        'color' => '#ff0000',
        'sort_order' => 0,
    ]);

    $config = $product->fresh()->load('variants')->viewerConfig();

    expect($config)
        ->toHaveKeys(['renderMode', 'modelUrl', 'labelTextureUrl', 'containerShape', 'baseColor', 'material', 'animationPreset', 'backgroundTheme', 'camera', 'variants'])
        ->not->toHaveKeys(['price', 'status', 'id', 'category_id'])
        ->and($config['renderMode'])->toBe('procedural')
        ->and($config['baseColor'])->toBe('#112233')
        ->and($config['variants'])->toHaveCount(1)
        ->and($config['variants'][0]['color'])->toBe('#ff0000');
});

it('falls back from model3d without a glb file', function () {
    $withShape = Product::factory()->create(['render_mode' => RenderMode::Model3d]);
    $withoutShape = Product::factory()->create(['render_mode' => RenderMode::Model3d, 'container_shape' => null]);

    expect($withShape->effectiveRenderMode())->toBe(RenderMode::Procedural)
        ->and($withoutShape->effectiveRenderMode())->toBe(RenderMode::Image);
});

it('replaces the explode preset when there is no glb model', function () {
    $product = Product::factory()->create([
        'container_shape' => ContainerShape::Jar,
        'animation_preset' => AnimationPreset::Explode,
    ]);

    expect($product->viewerConfig()['animationPreset'])->toBe('rotate');
});

it('stores and falls back between translations', function () {
    $product = Product::factory()->create([
        'name' => ['id' => 'Serum Cerah', 'en' => null],
    ]);

    app()->setLocale('en');

    expect($product->fresh()->name)->toBe('Serum Cerah');
});

it('casts enums', function () {
    $product = Product::factory()->create();

    expect($product->render_mode)->toBeInstanceOf(RenderMode::class)
        ->and(RenderMode::from('image')->getLabel())->toBeString()
        ->and(AnimationPreset::Explode->requiresModel())->toBeTrue()
        ->and(AnimationPreset::Rotate->requiresModel())->toBeFalse();
});
