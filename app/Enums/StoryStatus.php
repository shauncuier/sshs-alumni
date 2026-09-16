<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum StoryStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
}
