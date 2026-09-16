<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SettingGroup: string
{
    use HasLabel;

    case Organization = 'organization';
    case School = 'school';
    case Contact = 'contact';
    case Social = 'social';
    case Registration = 'registration';
    case Membership = 'membership';
    case Event = 'event';
    case Jubilee = 'jubilee';
    case Notification = 'notification';
    case Seo = 'seo';
    case Privacy = 'privacy';
    case System = 'system';
}
