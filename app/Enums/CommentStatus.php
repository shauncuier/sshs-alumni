<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CommentStatus: string
{
    use HasLabel;

    case Published = 'published';
    case Hidden = 'hidden';
    case Removed = 'removed';
}
