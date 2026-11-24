<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Services\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Catalog::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        // Uploads, deletions and finished image conversions change catalog output.
        Media::saved(fn () => Catalog::flush());
        Media::deleted(fn () => Catalog::flush());

        View::composer(['layouts.app', 'pages.*'], function ($view): void {
            $view->with('site', SiteSetting::current());
        });
    }
}
