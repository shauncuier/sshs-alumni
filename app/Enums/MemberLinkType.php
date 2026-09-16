<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum MemberLinkType: string
{
    use HasLabel;

    case Linkedin = 'linkedin';
    case Facebook = 'facebook';
    case Website = 'website';
    case X = 'x';
    case Youtube = 'youtube';
    case Other = 'other';
}
