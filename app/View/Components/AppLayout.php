<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/** Public site layout: <x-app-layout :title="..." :description="..." :image="..."> */
class AppLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public string $type = 'website',
    ) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
