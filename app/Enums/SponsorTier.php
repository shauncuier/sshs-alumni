<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SponsorTier: string
{
    use HasLabel;

    case Title = 'title';
    case Platinum = 'platinum';
    case Gold = 'gold';
    case Silver = 'silver';
    case Partner = 'partner';
    case Custom = 'custom';
}
