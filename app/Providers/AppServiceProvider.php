<?php

namespace App\Providers;

use App\Enums\PromotionPlacement;
use App\Models\SiteSetting;
use App\Services\Catalog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        View::composer('layouts.app', function ($view): void {
            $catalog = $this->app->make(Catalog::class);

            $view->with([
                'contentPages' => $catalog->pages(),
                'hasFaq' => $catalog->generalFaqs()->isNotEmpty(),
                'announcements' => $catalog->runningPromotions(PromotionPlacement::TopBar),
            ]);
        });

        // Contact, reseller and newsletter forms: enough for people, too few for spam bots.
        RateLimiter::for('public-forms', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
    }
}
