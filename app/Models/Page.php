<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageKey;
use App\Observers\CatalogCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/** Editable content of a fixed page (about, privacy policy, terms, reseller program). */
#[Fillable(['key', 'title', 'intro', 'content', 'meta_description'])]
#[ObservedBy(CatalogCacheObserver::class)]
class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'intro', 'content', 'meta_description'];

    protected function casts(): array
    {
        return [
            'key' => PageKey::class,
        ];
    }

    /** Rich-text content, sanitized for output with {!! !!}. */
    public function safeContent(): ?string
    {
        return $this->content ? Str::sanitizeHtml($this->content) : null;
    }

    public function metaDescription(): ?string
    {
        return $this->meta_description ?: $this->intro;
    }
}
