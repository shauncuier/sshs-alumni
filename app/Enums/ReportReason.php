<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ReportReason: string
{
    use HasLabel;

    case Spam = 'spam';
    case Abuse = 'abuse';
    case FalseInfo = 'false_info';
    case Harassment = 'harassment';
    case Other = 'other';
}
