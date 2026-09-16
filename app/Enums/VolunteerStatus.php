<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum VolunteerStatus: string
{
    use HasLabel;

    case Applied = 'applied';
    case Approved = 'approved';
    case Active = 'active';
    case Inactive = 'inactive';
    case Rejected = 'rejected';
}
