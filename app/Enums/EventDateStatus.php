<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EventDateStatus: string
{
    use HasLabel;

    case Tba = 'tba';
    case Announced = 'announced';
}
