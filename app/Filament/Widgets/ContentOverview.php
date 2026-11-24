<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Models\Faq;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Testimonial;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** How much trust and marketing content is live on the site. */
class ContentOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return __('admin.dashboard.content');
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $posts = Post::query()->published()->count();
        $draftPosts = Post::query()->where('status', ProductStatus::Draft)->count();
        $latestPost = Post::query()->published()->latest('published_at')->value('published_at');

        $testimonials = Testimonial::query()->where('is_published', true)->count();
        $featuredTestimonials = Testimonial::query()->where('is_published', true)->where('is_featured', true)->count();

        $generalFaqs = Faq::query()->where('is_published', true)->whereNull('product_id')->count();
        $productFaqs = Faq::query()->where('is_published', true)->whereNotNull('product_id')->count();

        $running = Promotion::query()->where('is_active', true)->get()->filter(fn (Promotion $promotion): bool => $promotion->isRunning());

        return [
            Stat::make(__('admin.dashboard.stats.posts'), $posts)
                ->description($latestPost
                    ? __('admin.dashboard.stats.posts_latest', ['date' => $latestPost->diffForHumans(), 'drafts' => $draftPosts])
                    : __('admin.dashboard.stats.posts_none'))
                ->icon('heroicon-o-newspaper')
                ->url(PostResource::getUrl()),

            Stat::make(__('admin.dashboard.stats.testimonials'), $testimonials)
                ->description(__('admin.dashboard.stats.testimonials_featured', ['count' => $featuredTestimonials]))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(TestimonialResource::getUrl()),

            Stat::make(__('admin.dashboard.stats.faqs'), $generalFaqs + $productFaqs)
                ->description(__('admin.dashboard.stats.faqs_detail', ['general' => $generalFaqs, 'product' => $productFaqs]))
                ->icon('heroicon-o-question-mark-circle')
                ->url(FaqResource::getUrl()),

            Stat::make(__('admin.dashboard.stats.promotions'), $running->count())
                ->description($running->isEmpty()
                    ? __('admin.dashboard.stats.promotions_none')
                    : $running->pluck('title')->take(2)->implode(', '))
                ->icon('heroicon-o-megaphone')
                ->color($running->isEmpty() ? 'gray' : 'success')
                ->url(PromotionResource::getUrl()),
        ];
    }
}
