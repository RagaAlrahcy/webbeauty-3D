<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\RenderMode;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Server-rendered fallback image + container that resources/js/three/mount.js
 * upgrades to a 3D viewer when the device can handle it.
 */
class ProductViewer extends Component
{
    /**
     * @param  bool  $priority  true for the LCP image (hero/detail): eager + fetchpriority=high
     */
    public function __construct(
        public Product $product,
        public bool $priority = false,
        public string $class = '',
        public bool $controls = true,
    ) {}

    public function is3d(): bool
    {
        return $this->product->effectiveRenderMode() !== RenderMode::Image;
    }

    public function background(): ?string
    {
        $theme = $this->product->background_theme;

        // Same rule as the admin form validation; anything else is ignored.
        if ($theme && preg_match('/^(#[0-9a-fA-F]{6}|(linear|radial)-gradient\([#0-9a-zA-Z%,.\s()-]+\))$/', $theme)) {
            return $theme;
        }

        return null;
    }

    public function render(): View
    {
        return view('components.product-viewer', [
            'config' => $this->product->viewerConfig(),
            'image' => $this->product->primaryImage(),
        ]);
    }
}
