<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum BatchStatus: string
{
    use HasLabel;

    case Active = 'active';
    case Archived = 'archived';
}
