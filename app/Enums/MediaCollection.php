<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum MediaCollection: string
{
    use HasLabel;

    case Profile = 'profile';
    case Gallery = 'gallery';
    case Cover = 'cover';
    case Banner = 'banner';
    case Logo = 'logo';
    case Document = 'document';
    case Attachment = 'attachment';
}
