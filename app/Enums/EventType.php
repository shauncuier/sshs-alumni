<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EventType: string
{
    use HasLabel;

    case Reunion = 'reunion';
    case Seminar = 'seminar';
    case Sports = 'sports';
    case Cultural = 'cultural';
    case Fundraising = 'fundraising';
    case Meeting = 'meeting';
    case Volunteer = 'volunteer';
    case General = 'general';
}
