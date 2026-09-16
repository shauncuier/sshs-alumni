# 04 — Roles & Permissions (RBAC)

Implemented with `spatie/laravel-permission`. Permissions are assigned to roles; roles are assigned to users. Direct user permissions are supported but not used by default.

**Authorization is enforced server-side.** The client receives `auth.permissions` purely so buttons can be hidden. Hiding a button is a courtesy; the policy is the control.

---

## 1. Permission naming

`{module}.{action}` — lowercase, dot-separated.

Actions: `view`, `create`, `edit`, `delete`, plus module-specific verbs (`verify`, `publish`, `checkin`, `refund`, `moderate`, `send`, `export`, `manage`).

## 2. Permission catalogue

| Module | Permissions |
|---|---|
| `admin` | `admin.access` |
| `members` | `members.view`, `members.create`, `members.edit`, `members.verify`, `members.delete`, `members.export` |
| `crm` | `crm.view`, `crm.manage`, `crm.assign`, `crm.delete` |
| `batches` | `batches.view`, `batches.create`, `batches.edit`, `batches.delete` |
| `events` | `events.view`, `events.create`, `events.edit`, `events.publish`, `events.checkin`, `events.delete` |
| `payments` | `payments.view`, `payments.create`, `payments.edit`, `payments.refund` |
| `donations` | `donations.view`, `donations.manage` |
| `sponsors` | `sponsors.view`, `sponsors.manage` |
| `volunteers` | `volunteers.view`, `volunteers.manage` |
| `committees` | `committees.view`, `committees.manage` |
| `community` | `community.view`, `community.moderate`, `community.delete` |
| `content` | `content.view`, `content.manage`, `content.publish` |
| `campaigns` | `campaigns.manage`, `campaigns.send` |
| `reports` | `reports.view`, `reports.export` |
| `users` | `users.manage` |
| `roles` | `roles.manage` |
| `audit` | `audit.view` |
| `settings` | `settings.manage` |

**60 permissions.**

## 3. Roles

| Role | Purpose |
|---|---|
| **Super Admin** | Full system access. Bypasses all checks via `Gate::before`. At least one must always exist. |
| **Admin** | Everything except role management edge cases; the day-to-day committee administrator. |
| **CRM Manager** | Owns contacts, pipeline, activities, tasks, donor and sponsor relationships. |
| **Membership Manager** | Owns the registration → verification → membership-number workflow and the batch structure. |
| **Event Manager** | Owns events, tickets, registrations and check-in. |
| **Finance Manager** | Owns payments, membership fees, donations, sponsorships and financial reports. |
| **Content Manager** | Owns the CMS: news, announcements, gallery, pages, stories, school history, FAQs. |
| **Moderator** | Owns community moderation and the report queue. |
| **Batch Coordinator** | Limited management of **their own batch only**. |
| **Volunteer Coordinator** | Owns volunteers, teams, assignments; can check in attendees. |
| **Member** | Default role for every approved alumnus. No admin access. |

## 4. Role → permission matrix

`✔` = full set · `view` = read only · `–` = none · scoped entries explained in §5.

| Permission group | Super Admin | Admin | CRM Mgr | Membership Mgr | Event Mgr | Finance Mgr | Content Mgr | Moderator | Batch Coord | Volunteer Coord | Member |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `admin.access` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | – |
| `members.view` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | – | ✔ | *batch* | ✔ | *own* |
| `members.create` | ✔ | ✔ | – | ✔ | – | – | – | – | – | – | – |
| `members.edit` | ✔ | ✔ | – | ✔ | – | – | – | – | – | – | *own* |
| `members.verify` | ✔ | ✔ | – | ✔ | – | – | – | – | – | – | – |
| `members.delete` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |
| `members.export` | ✔ | ✔ | ✔ | ✔ | – | – | – | – | – | – | – |
| `crm.view` | ✔ | ✔ | ✔ | ✔ | – | ✔ | – | – | – | – | – |
| `crm.manage` | ✔ | ✔ | ✔ | – | – | – | – | – | – | – | – |
| `crm.assign` | ✔ | ✔ | ✔ | – | – | – | – | – | – | – | – |
| `crm.delete` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |
| `batches.view` | ✔ | ✔ | ✔ | ✔ | ✔ | – | ✔ | ✔ | ✔ | ✔ | ✔ |
| `batches.create` | ✔ | ✔ | – | ✔ | – | – | – | – | – | – | – |
| `batches.edit` | ✔ | ✔ | – | ✔ | – | – | – | – | *own* | – | – |
| `batches.delete` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |
| `events.view` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | – | ✔ | ✔ | ✔ |
| `events.create` | ✔ | ✔ | – | – | ✔ | – | – | – | – | – | – |
| `events.edit` | ✔ | ✔ | – | – | ✔ | – | – | – | – | – | – |
| `events.publish` | ✔ | ✔ | – | – | ✔ | – | – | – | – | – | – |
| `events.checkin` | ✔ | ✔ | – | – | ✔ | – | – | – | – | ✔ | – |
| `events.delete` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |
| `payments.view` | ✔ | ✔ | ✔ | – | ✔ | ✔ | – | – | – | – | *own* |
| `payments.create` | ✔ | ✔ | – | – | – | ✔ | – | – | – | – | – |
| `payments.edit` | ✔ | ✔ | – | – | – | ✔ | – | – | – | – | – |
| `payments.refund` | ✔ | ✔ | – | – | – | ✔ | – | – | – | – | – |
| `donations.view` | ✔ | ✔ | ✔ | – | – | ✔ | – | – | – | – | *own* |
| `donations.manage` | ✔ | ✔ | ✔ | – | – | ✔ | – | – | – | – | – |
| `sponsors.view` | ✔ | ✔ | ✔ | – | ✔ | ✔ | ✔ | – | – | – | – |
| `sponsors.manage` | ✔ | ✔ | ✔ | – | – | ✔ | – | – | – | – | – |
| `volunteers.view` | ✔ | ✔ | ✔ | – | ✔ | – | – | – | – | ✔ | *own* |
| `volunteers.manage` | ✔ | ✔ | – | – | – | – | – | – | – | ✔ | – |
| `committees.view` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| `committees.manage` | ✔ | ✔ | – | – | – | – | ✔ | – | – | – | – |
| `community.view` | ✔ | ✔ | – | – | – | – | ✔ | ✔ | ✔ | – | ✔ |
| `community.moderate` | ✔ | ✔ | – | – | – | – | – | ✔ | *batch* | – | – |
| `community.delete` | ✔ | ✔ | – | – | – | – | – | ✔ | – | – | – |
| `content.view` | ✔ | ✔ | – | – | ✔ | – | ✔ | ✔ | ✔ | – | – |
| `content.manage` | ✔ | ✔ | – | – | – | – | ✔ | – | *batch* | – | – |
| `content.publish` | ✔ | ✔ | – | – | – | – | ✔ | – | – | – | – |
| `campaigns.manage` | ✔ | ✔ | ✔ | ✔ | – | – | ✔ | – | – | – | – |
| `campaigns.send` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |
| `reports.view` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | – | – | – | – | – |
| `reports.export` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | – | – | – | – | – |
| `users.manage` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |
| `roles.manage` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |
| `audit.view` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |
| `settings.manage` | ✔ | ✔ | – | – | – | – | – | – | – | – | – |

## 5. Scoped permissions

A permission name grants **capability**. A policy grants **reach**. Three roles are scoped, and the scope lives in the policy, never in the permission string.

### Batch Coordinator

Holds `members.view`, `batches.edit`, `community.moderate`, `content.manage` — but every one is narrowed:

```php
// MemberPolicy::view()
public function view(User $user, Member $member): bool
{
    if ($user->can('members.view') && ! $user->hasRole('Batch Coordinator')) {
        return true;
    }

    return $user->member?->coordinatedBatchIds()->contains($member->batch_id) ?? false;
}
```

Reach: members of their own batch, their own batch record, posts where `posts.batch_id` matches, and batch-scoped announcements and gallery albums. Nothing else.

### Member

Holds no admin permissions. `*own*` in the matrix is not a permission — it is a policy allowing a user to read and edit the `Member`, `Payment`, `Donation` and `Volunteer` rows that belong to them.

### Volunteer Coordinator

Holds `events.checkin` without `events.edit`. They can staff and run the gate; they cannot change the event.

## 6. Implementation

### Super Admin bypass

```php
// AuthServiceProvider::boot()
Gate::before(fn (User $user) => $user->hasRole('Super Admin') ? true : null);
```

Returning `null` (not `false`) lets other gates continue to run for everyone else.

### Route protection

```php
Route::middleware('can:members.verify')->group(function () {
    Route::post('members/{member}/approve', [VerificationController::class, 'approve']);
});
```

### Policy protection

Every model with non-trivial access has a policy. Controllers call `$this->authorize()` or the Form Request's `authorize()` — never an inline `if` on a role name.

**Roles are never checked in application logic except in `Gate::before` and the scoping policies above.** Everything else checks permissions, so the committee can re-cut roles from the admin UI without a deployment.

### Frontend

`HandleInertiaRequests::share()` exposes:

```php
'auth' => [
    'user' => $request->user(),
    'permissions' => $request->user()?->getAllPermissions()->pluck('name') ?? [],
    'roles' => $request->user()?->getRoleNames() ?? [],
],
```

```tsx
const can = usePermission();
{can('members.verify') && <ApproveButton />}
```

This hides UI. It does not protect anything.

## 7. Seeding

`RolePermissionSeeder` creates all 60 permissions and all 11 roles idempotently (`firstOrCreate`), so re-running after adding a permission is safe.

`DemoSeeder` — **development only**, refuses to run when `app()->isProduction()` — creates:

| Email | Role | Password |
|---|---|---|
| `admin@example.test` | Super Admin | `ChangeMe123!` |

> **This password must be changed before production.** See [11-installation.md](11-installation.md) and [13-deployment.md](13-deployment.md).

## 8. Tests

Required in [14-testing.md](14-testing.md):

- Every admin route returns 403 for a user without the permission.
- Every admin route returns 403 for a guest (redirect to login).
- Super Admin reaches every route.
- A Batch Coordinator cannot view, edit or moderate outside their batch.
- A Member cannot read another member's payments, donations or private profile fields.
- A Volunteer Coordinator can check in but cannot edit the event.
- Removing a permission from a role takes effect on the next request (cache invalidation).
