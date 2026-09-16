<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CommitteeType: string
{
    use HasLabel;

    case Executive = 'executive';
    case Organizing = 'organizing';
    case Event = 'event';
    case Finance = 'finance';
    case Media = 'media';
    case Volunteer = 'volunteer';
    case Batch = 'batch';
}
