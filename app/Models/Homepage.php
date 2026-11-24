<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CatalogCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

#[Fillable(['hero_product_id', 'headline', 'subheadline', 'cta_label', 'cta_url'])]
#[ObservedBy(CatalogCacheObserver::class)]
class Homepage extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['headline', 'subheadline', 'cta_label'];

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([]);
    }

    public function heroProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'hero_product_id');
    }
}
