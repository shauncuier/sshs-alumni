<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ContentStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
