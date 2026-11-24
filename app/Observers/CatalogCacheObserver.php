<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Catalog;
use Illuminate\Database\Eloquent\Model;

/** Clears cached catalog queries whenever catalog content changes. */
class CatalogCacheObserver
{
    public function saved(Model $model): void
    {
        Catalog::flush();
    }

    public function deleted(Model $model): void
    {
        Catalog::flush();
    }

    public function restored(Model $model): void
    {
        Catalog::flush();
    }
}
