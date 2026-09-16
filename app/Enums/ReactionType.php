<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ReactionType: string
{
    use HasLabel;

    case Like = 'like';
    case Love = 'love';
    case Celebrate = 'celebrate';
    case Support = 'support';
}
