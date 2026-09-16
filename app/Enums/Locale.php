<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Locale: string
{
    use HasLabel;

    case Bn = 'bn';
    case En = 'en';
}
