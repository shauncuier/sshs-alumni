<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EventStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Published = 'published';
    case RegistrationOpen = 'registration_open';
    case RegistrationClosed = 'registration_closed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
