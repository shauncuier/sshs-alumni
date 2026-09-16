<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AudienceType: string
{
    use HasLabel;

    case AllMembers = 'all_members';
    case Batch = 'batch';
    case Status = 'status';
    case Role = 'role';
    case Custom = 'custom';
}
