<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Member;
use App\Models\Post;

class MentionNotification extends AppNotification
{
    public function __construct(
        public Member $author,
        public Post $post,
        public ?Comment $comment = null,
    ) {
        $authorName = $author->full_name;
        $title = "{$authorName} mentioned you";
        $body = $comment !== null
            ? "{$authorName} mentioned you in a comment on: {$post->title}"
            : "{$authorName} mentioned you in a community post: {$post->title}";

        $url = route('community.show', $post->ulid);

        parent::__construct(
            title: $title,
            body: $body,
            actionUrl: $url,
            actionText: 'View Discussion',
            category: 'community',
            icon: 'AtSign',
        );
    }
}
