<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CrmContact;
use App\Models\User;

/**
 * Who may read and act on a CRM contact.
 *
 * Deliberately NOT owner-scoped for reading. An association's CRM is a shared
 * record: a volunteer coordinator needs to see that the membership secretary
 * already called someone, and a contact nobody can see is a contact nobody
 * follows up. Ownership here answers "whose job is this", not "who may look".
 *
 * Super Admin bypasses all of this via Gate::before.
 *
 * @see docs/04-roles-permissions.md section 5
 */
class CrmContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.view');
    }

    public function view(User $user, CrmContact $contact): bool
    {
        return $user->can('crm.view');
    }

    public function create(User $user): bool
    {
        return $user->can('crm.manage');
    }

    public function update(User $user, CrmContact $contact): bool
    {
        return $user->can('crm.manage');
    }

    /**
     * Reassigning someone else's contact is a separate permission from editing
     * one: taking work off a colleague's list is a supervisory act, and the
     * owner themselves can always hand it on.
     */
    public function assign(User $user, CrmContact $contact): bool
    {
        if ($contact->owner_id === $user->id) {
            return true;
        }

        return $user->can('crm.assign');
    }

    public function delete(User $user, CrmContact $contact): bool
    {
        return $user->can('crm.delete');
    }
}
