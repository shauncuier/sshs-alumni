<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;

/**
 * Who may read and act on an event.
 *
 * `events.checkin` is deliberately separate from `events.edit`: a volunteer
 * runs the gate on the day without being able to change the event, its prices
 * or its schedule. That separation is the whole reason the permission exists.
 *
 * Super Admin bypasses all of this via Gate::before.
 *
 * @see docs/04-roles-permissions.md section 5
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('events.view');
    }

    public function view(User $user, Event $event): bool
    {
        return $user->can('events.view');
    }

    public function create(User $user): bool
    {
        return $user->can('events.create');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can('events.edit');
    }

    /**
     * Publishing is what makes an event public, and announcing the Jubilee
     * date is the same act — so both sit behind `events.publish` rather than
     * the broader edit permission.
     */
    public function publish(User $user, Event $event): bool
    {
        return $user->can('events.publish');
    }

    public function checkin(User $user, Event $event): bool
    {
        return $user->can('events.checkin');
    }

    /**
     * A completed event is the association's record of what happened. Deleting
     * one destroys its attendance and its payment trail, so it is refused even
     * to someone holding `events.delete` — it is archived instead.
     */
    public function delete(User $user, Event $event): bool
    {
        if ($event->status === EventStatus::Completed) {
            return false;
        }

        return $user->can('events.delete');
    }
}
