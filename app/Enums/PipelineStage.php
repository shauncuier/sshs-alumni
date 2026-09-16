<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PipelineStage: string
{
    use HasLabel;

    case New = 'new';
    case Contacted = 'contacted';
    case Interested = 'interested';
    case Registered = 'registered';
    case Verified = 'verified';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
