<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Newsletter subscriber collected from the footer form. */
#[Fillable(['email', 'locale'])]
class Subscriber extends Model {}
