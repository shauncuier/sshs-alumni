<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PostCategory: string
{
    use HasLabel;

    case General = 'general';
    case Reunion = 'reunion';
    case Batch = 'batch';
    case Memories = 'memories';
    case Career = 'career';
    case Business = 'business';
    case Support = 'support';
    case Volunteer = 'volunteer';
    case Jubilee = 'jubilee';
}
