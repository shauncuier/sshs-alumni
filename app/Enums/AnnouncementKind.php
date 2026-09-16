<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AnnouncementKind: string
{
    use HasLabel;

    case Announcement = 'announcement';
    case Notice = 'notice';
}
