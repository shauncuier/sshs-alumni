<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum FaqGroup: string
{
    use HasLabel;

    case General = 'general';
    case Jubilee = 'jubilee';
    case Membership = 'membership';
    case Payment = 'payment';
    case Event = 'event';
}
