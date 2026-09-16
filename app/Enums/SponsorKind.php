<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SponsorKind: string
{
    use HasLabel;

    case Individual = 'individual';
    case Company = 'company';
}
