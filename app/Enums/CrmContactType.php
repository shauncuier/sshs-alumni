<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum CrmContactType: string
{
    use HasLabel;

    case Prospect = 'prospect';
    case Volunteer = 'volunteer';
    case Donor = 'donor';
    case Sponsor = 'sponsor';
    case Guest = 'guest';
    case Partner = 'partner';
    case Organization = 'organization';
    case Other = 'other';
}
