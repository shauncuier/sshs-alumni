<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Announcement;

class AnnouncementPublishedNotification extends AppNotification
{
    public function __construct(Announcement $announcement)
    {
        parent::__construct(
            title: 'New Announcement',
            body: $announcement->title,
            actionUrl: route('announcements.index'),
            actionText: 'Read Notice',
            category: 'announcement',
            icon: 'Megaphone',
        );
    }
}
