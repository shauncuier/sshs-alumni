<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AnnouncementLevel: string
{
    use HasLabel;

    case Info = 'info';
    case Important = 'important';
    case Urgent = 'urgent';
}
