<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AudienceScope: string
{
    use HasLabel;

    case Public = 'public';
    case Members = 'members';
    case Batch = 'batch';
    case Role = 'role';
}
