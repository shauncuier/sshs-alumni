<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ReportStatus: string
{
    use HasLabel;

    case Open = 'open';
    case Reviewing = 'reviewing';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    /**
     * A report nobody has to look at again.
     *
     * `dismissed` closes a report exactly as firmly as `resolved` does: a
     * moderator looked and decided nothing was wrong, which is an answer.
     */
    public function isClosed(): bool
    {
        return $this === self::Resolved || $this === self::Dismissed;
    }
}
