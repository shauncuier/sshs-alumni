# 03 — Route Map

`routes/web.php` becomes a loader:

```php
require __DIR__.'/public.php';
require __DIR__.'/member.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';   // existing, unchanged
```

Fortify registers all authentication routes (`/login`, `/register`, `/forgot-password`, `/reset-password`, `/verify-email`, `/two-factor-challenge`, `/confirm-password`, passkey endpoints). Those are untouched.

Route names are used everywhere via `route()` and Wayfinder-generated TypeScript. **No hard-coded URLs in React.** See the `wayfinder-development` skill.

---

## 1. Public — `routes/public.php`

No authentication. Indexed by search engines except where noted.

| Method | URI                     | Name                  | Controller                                            |
| ------ | ----------------------- | --------------------- | ----------------------------------------------------- |
| GET    | `/`                     | `home`                | `Public\HomeController@index`                         |
| GET    | `/about`                | `about`               | `Public\AboutController@association`                  |
| GET    | `/about/school`         | `about.school`        | `Public\AboutController@school`                       |
| GET    | `/jubilee`              | `jubilee`             | `Public\JubileeController@index`                      |
| GET    | `/jubilee/schedule`     | `jubilee.schedule`    | `Public\JubileeController@schedule`                   |
| GET    | `/jubilee/sponsors`     | `jubilee.sponsors`    | `Public\JubileeController@sponsors`                   |
| GET    | `/jubilee/faq`          | `jubilee.faq`         | `Public\JubileeController@faq`                        |
| GET    | `/jubilee/register`     | `jubilee.register`    | `Public\JubileeController@register`                   |
| GET    | `/events`               | `events.index`        | `Public\EventController@index`                        |
| GET    | `/events/{event:slug}`  | `events.show`         | `Public\EventController@show`                         |
| GET    | `/batches`              | `batches.index`       | `Public\BatchController@index`                        |
| GET    | `/batches/{batch:slug}` | `batches.show`        | `Public\BatchController@show`                         |
| GET    | `/news`                 | `news.index`          | `Public\NewsController@index`                         |
| GET    | `/news/{news:slug}`     | `news.show`           | `Public\NewsController@show`                          |
| GET    | `/announcements`        | `announcements.index` | `Public\AnnouncementController@index`                 |
| GET    | `/gallery`              | `gallery.index`       | `Public\GalleryController@index`                      |
| GET    | `/gallery/{album:slug}` | `gallery.show`        | `Public\GalleryController@show`                       |
| GET    | `/committees`           | `committees.index`    | `Public\CommitteeController@index`                    |
| GET    | `/stories`              | `stories.index`       | `Public\StoryController@index`                        |
| GET    | `/stories/{story:slug}` | `stories.show`        | `Public\StoryController@show`                         |
| GET    | `/achievements`         | `achievements`        | `Public\AchievementController@index`                  |
| GET    | `/donate`               | `donate`              | `Public\DonationController@create`                    |
| POST   | `/donate`               | `donate.store`        | `Public\DonationController@store` · `throttle:6,1`    |
| GET    | `/sponsorship`          | `sponsorship`         | `Public\SponsorshipController@index`                  |
| POST   | `/sponsorship/enquiry`  | `sponsorship.enquiry` | `Public\SponsorshipController@store` · `throttle:6,1` |
| GET    | `/contact`              | `contact`             | `Public\ContactController@show`                       |
| POST   | `/contact`              | `contact.store`       | `Public\ContactController@store` · `throttle:6,1`     |
| GET    | `/p/{page:slug}`        | `pages.show`          | `Public\PageController@show` — privacy policy, terms  |
| GET    | `/locale/{locale}`      | `locale.switch`       | `Public\LocaleController@switch`                      |
| GET    | `/sitemap.xml`          | `sitemap`             | `Public\SitemapController@index`                      |
| GET    | `/robots.txt`           | `robots`              | `Public\SitemapController@robots`                     |

### Membership registration (multi-step)

| Method | URI            | Name          | Notes                                                              |
| ------ | -------------- | ------------- | ------------------------------------------------------------------ |
| GET    | `/join`        | `join.start`  | redirects to step 1                                                |
| GET    | `/join/{step}` | `join.step`   | `step` ∈ `basic`, `academic`, `professional`, `location`, `review` |
| POST   | `/join/{step}` | `join.store`  | per-step validation, draft persisted in session · `throttle:20,1`  |
| POST   | `/join/submit` | `join.submit` | final submit · `throttle:5,1`                                      |
| GET    | `/join/done`   | `join.done`   | confirmation                                                       |

### Public member verification (QR target)

| Method | URI                     | Name            | Notes                      |
| ------ | ----------------------- | --------------- | -------------------------- |
| GET    | `/verify/member/{ulid}` | `verify.member` | `throttle:30,1`, `noindex` |

Returns **name, batch, membership number, photo, verification status only**. Nothing else, ever. See [08-security-privacy.md](08-security-privacy.md).

### Not public

`/directory` is **not** a public route. Per the agreed decision, the alumni directory requires an authenticated, approved member. The public `/batches` page shows aggregate counts only. An admin setting (`privacy.public_directory`) can open it later without a code change.

---

## 2. Member — `routes/member.php`

```php
Route::middleware(['auth', 'verified'])->group(function () { … });
```

| Method | URI                                   | Name                     | Controller                                                             |
| ------ | ------------------------------------- | ------------------------ | ---------------------------------------------------------------------- |
| GET    | `/dashboard`                          | `dashboard`              | `Member\DashboardController` — role-aware; admins redirect to `/admin` |
| GET    | `/my/profile`                         | `my.profile`             | `Member\ProfileController@edit`                                        |
| PATCH  | `/my/profile`                         | `my.profile.update`      | `Member\ProfileController@update`                                      |
| POST   | `/my/profile/photo`                   | `my.profile.photo`       | `Member\ProfileController@updatePhoto`                                 |
| PATCH  | `/my/privacy`                         | `my.privacy.update`      | `Member\ProfileController@updatePrivacy`                               |
| GET    | `/my/card`                            | `my.card`                | `Member\MembershipCardController@show`                                 |
| GET    | `/my/card/download`                   | `my.card.download`       | PDF / print view                                                       |
| GET    | `/my/events`                          | `my.events`              | `Member\MyEventController@index`                                       |
| GET    | `/my/events/{registration:ulid}`      | `my.events.ticket`       | QR pass                                                                |
| POST   | `/events/{event:slug}/register`       | `events.register`        | `Member\MyEventController@store` · `throttle:10,1`                     |
| DELETE | `/my/events/{registration}`           | `my.events.cancel`       |                                                                        |
| GET    | `/my/payments`                        | `my.payments`            | `Member\MyPaymentController@index`                                     |
| GET    | `/my/payments/{payment:ulid}/receipt` | `my.payments.receipt`    |                                                                        |
| GET    | `/my/donations`                       | `my.donations`           | `Member\MyDonationController@index`                                    |
| GET    | `/my/batch`                           | `my.batch`               | `Member\BatchController` · approved members only                       |
| GET    | `/notifications`                      | `notifications.index`    | `Member\NotificationController@index`                                  |
| POST   | `/notifications/{id}/read`            | `notifications.read`     |                                                                        |
| POST   | `/notifications/read-all`             | `notifications.read-all` |                                                                        |

### Approved members only

```php
Route::middleware(['auth', 'verified', 'member.approved'])->group(function () { … });
```

| Method       | URI                          | Name                                     |
| ------------ | ---------------------------- | ---------------------------------------- |
| GET          | `/directory`                 | `directory.index`                        |
| GET          | `/directory/{member:ulid}`   | `directory.show`                         |
| GET          | `/community`                 | `community.index`                        |
| GET          | `/community/{post:ulid}`     | `community.show`                         |
| POST         | `/community`                 | `community.store` · `throttle:10,1`      |
| PATCH/DELETE | `/community/{post}`          | `community.update` / `community.destroy` |
| POST         | `/community/{post}/comments` | `community.comment` · `throttle:20,1`    |
| DELETE       | `/comments/{comment}`        | `comments.destroy`                       |
| POST         | `/community/{post}/react`    | `community.react`                        |
| POST         | `/reports`                   | `reports.store` · `throttle:10,1`        |
| GET/POST     | `/my/stories`                | `my.stories.*` — submit an alumni story  |

`EnsureMemberApproved` rejects members whose `status` is not `approved`, and renders a status page explaining where they are in the verification workflow rather than a bare 403.

---

## 3. Admin — `routes/admin.php`

```php
Route::middleware(['auth', 'verified', 'can:admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () { … });
```

`admin.access` guards the whole group, and every sub-group carries its own
`can:` middleware on top. Both are needed, not one or the other: an ordinary
Member holds `batches.view` so they can read the PUBLIC batch pages, and with
per-module gating alone that same permission would have opened the admin
panel. A test walks the live route list and fails if any admin route is
missing either gate.

`LogAdminAction` middleware is applied to the whole group.

| URI (under `/admin`)                              | Name prefix                           | Permission                                                            |
| ------------------------------------------------- | ------------------------------------- | --------------------------------------------------------------------- |
| `/`                                               | `admin.dashboard`                     | `admin.access`                                                        |
| `/search`                                         | `admin.search`                        | `admin.access`                                                        |
| **Members**                                       |                                       |                                                                       |
| `/members` (index, create, show, edit, destroy)   | `admin.members.*`                     | `members.view` / `members.create` / `members.edit` / `members.delete` |
| `/members/{member}/verification`                  | `admin.members.verification`          | `members.verify`                                                      |
| `/members/{member}/approve` (POST)                | `admin.members.approve`               | `members.verify`                                                      |
| `/members/{member}/reject` (POST)                 | `admin.members.reject`                | `members.verify`                                                      |
| `/members/{member}/request-correction` (POST)     | `admin.members.correction`            | `members.verify`                                                      |
| `/members/{member}/suspend` (POST)                | `admin.members.suspend`               | `members.verify`                                                      |
| `/members/{member}/membership-number` (POST)      | `admin.members.number`                | `members.verify`                                                      |
| `/members/import`                                 | `admin.members.import`                | `members.create`                                                      |
| **Batches**                                       |                                       |                                                                       |
| `/batches` (index)                                | `admin.batches.index`                 | `batches.view`                                                        |
| `/batches/{batch}` (show)                         | `admin.batches.show`                  | `batches.view`                                                        |
| `/batches` (POST)                                 | `admin.batches.store`                 | `batches.create`                                                      |
| `/batches/{batch}` (PUT)                          | `admin.batches.update`                | `batches.edit` + `BatchPolicy`                                        |
| `/batches/{batch}/coordinators` (POST)            | `admin.batches.coordinators.store`    | `batches.edit` + `BatchPolicy`                                        |
| `/batches/{batch}/coordinators/{member}` (DELETE) | `admin.batches.coordinators.destroy`  | `batches.edit` + `BatchPolicy`                                        |
| **Events**                                        |                                       |                                                                       |
| `/events` (index, show)                           | `admin.events.*`                      | `events.view`                                                         |
| `/events` (POST)                                  | `admin.events.store`                  | `events.create`                                                       |
| `/events/{event}` (PUT)                           | `admin.events.update`                 | `events.edit`                                                         |
| `/events/{event}/status` (POST)                   | `admin.events.transition`             | `events.publish`                                                      |
| `/events/{event}/date` (POST)                     | `admin.events.date`                   | `events.publish` — THE DATE RULE lives here                           |
| `/events/{event}/tickets` (POST, PUT, DELETE)     | `admin.events.tickets.*`              | `events.edit`                                                         |
| `/events/{event}/registrations`                   | `admin.events.registrations`          | `events.view`                                                         |
| `/events/{event}/registrations` (POST, walk-in)   | `admin.events.registrations.store`    | `events.edit`                                                         |
| `/events/{event}/registrations/{r}/promote`       | `admin.events.promote`                | `events.edit`                                                         |
| `/events/{event}/checkin`                         | `admin.events.checkin`                | `events.checkin` + `throttle:120,1`                                   |
| `/events/{event}/checkin/{ulid}` (scan, GET)      | `admin.events.checkin.scan`           | `events.checkin` — shows, does NOT admit                              |
| `/events/{event}/checkin/{registration}` (POST)   | `admin.events.checkin.store`          | `events.checkin`                                                      |
| **CRM**                                           |                                       |                                                                       |
| `/crm/contacts` (index, show)                     | `admin.crm.contacts.*`                | `crm.view`                                                            |
| `/crm/contacts` (POST), `/{contact}` (PUT)        | `admin.crm.contacts.store/update`     | `crm.manage`                                                          |
| `/crm/contacts/{contact}/stage` (POST)            | `admin.crm.contacts.move`             | `crm.manage` — the ONLY place a stage moves                           |
| `/crm/contacts/{contact}/link` (POST, DELETE)     | `admin.crm.contacts.link/unlink`      | `crm.manage`                                                          |
| `/crm/contacts/{contact}/activities` (POST)       | `admin.crm.contacts.activities.store` | `crm.manage` — `system` is never accepted                             |
| `/crm/contacts/{contact}/tags` (POST, toggle)     | `admin.crm.contacts.tags.toggle`      | `crm.manage`                                                          |
| `/crm/contacts/{contact}/owner` (POST)            | `admin.crm.contacts.assign`           | `crm.assign`                                                          |
| `/crm/contacts/{contact}` (DELETE)                | `admin.crm.contacts.destroy`          | `crm.delete`                                                          |
| `/crm/pipeline`                                   | `admin.crm.pipeline`                  | `crm.view`                                                            |
| `/crm/tasks` (index, store)                       | `admin.crm.tasks.*`                   | `crm.view` / `crm.manage`                                             |
| `/crm/tasks/{task}` (PUT, DELETE)                 | `admin.crm.tasks.update/destroy`      | `crm.view` + CrmTaskPolicy (the assignee works their own)             |
| `/crm/tags` (index, store, update, destroy)       | `admin.crm.tags.*`                    | `crm.view` / `crm.manage`                                             |
| `/members/{member}/activities` (POST)             | `admin.members.activities.store`      | `crm.manage`                                                          |
| **Batches**                                       |                                       |                                                                       |
| `/batches` (resource)                             | `admin.batches.*`                     | `batches.view` / `batches.edit`                                       |
| `/batches/{batch}/coordinators`                   | `admin.batches.coordinators`          | `batches.edit`                                                        |
| **Events**                                        |                                       |                                                                       |
| `/events` (resource)                              | `admin.events.*`                      | `events.view` / `events.create` / `events.edit`                       |
| `/events/{event}/publish` (POST)                  | `admin.events.publish`                | `events.publish`                                                      |
| `/events/{event}/tickets`                         | `admin.events.tickets`                | `events.edit`                                                         |
| `/events/{event}/registrations`                   | `admin.events.registrations`          | `events.view`                                                         |
| `/events/{event}/checkin`                         | `admin.events.checkin`                | `events.checkin`                                                      |
| `/events/{event}/checkin/scan` (POST)             | `admin.events.checkin.scan`           | `events.checkin` · `throttle:120,1`                                   |
| **Golden Jubilee**                                |                                       |                                                                       |
| `/jubilee`                                        | `admin.jubilee.edit`                  | `events.edit`                                                         |
| `/jubilee/announce-date` (POST)                   | `admin.jubilee.announce`              | `events.publish`                                                      |
| `/jubilee/schedule`                               | `admin.jubilee.schedule`              | `events.edit`                                                         |
| **Money**                                         |                                       |                                                                       |
| `/payments` (index, show)                         | `admin.payments.*`                    | `payments.view`                                                       |
| `/payments/record` (GET/POST)                     | `admin.payments.record`               | `payments.create`                                                     |
| `/payments/{payment}/refund` (POST)               | `admin.payments.refund`               | `payments.refund`                                                     |
| `/membership-fees`                                | `admin.fees.*`                        | `payments.view`                                                       |
| `/donations` (resource)                           | `admin.donations.*`                   | `donations.view` / `donations.manage`                                 |
| `/sponsors` (resource)                            | `admin.sponsors.*`                    | `sponsors.view` / `sponsors.manage`                                   |
| `/sponsorship-packages` (resource)                | `admin.packages.*`                    | `sponsors.manage`                                                     |
| **People**                                        |                                       |                                                                       |
| `/volunteers` (resource)                          | `admin.volunteers.*`                  | `volunteers.view` / `volunteers.manage`                               |
| `/volunteer-teams` (resource)                     | `admin.volunteer-teams.*`             | `volunteers.manage`                                                   |
| `/committees` (resource)                          | `admin.committees.*`                  | `committees.view` / `committees.manage`                               |
| **Community**                                     |                                       |                                                                       |
| `/community/posts`                                | `admin.community.posts`               | `community.moderate`                                                  |
| `/community/reports`                              | `admin.community.reports`             | `community.moderate`                                                  |
| **CMS**                                           |                                       |                                                                       |
| `/news` (resource)                                | `admin.news.*`                        | `content.manage`                                                      |
| `/announcements` (resource)                       | `admin.announcements.*`               | `content.manage`                                                      |
| `/gallery` (resource) + `/gallery/{album}/images` | `admin.gallery.*`                     | `content.manage`                                                      |
| `/pages` (resource)                               | `admin.pages.*`                       | `content.manage`                                                      |
| `/stories` (resource)                             | `admin.stories.*`                     | `content.manage`                                                      |
| `/school-history` (resource)                      | `admin.history.*`                     | `content.manage`                                                      |
| `/faqs` (resource)                                | `admin.faqs.*`                        | `content.manage`                                                      |
| `/media`                                          | `admin.media.*`                       | `content.manage`                                                      |
| **Communication**                                 |                                       |                                                                       |
| `/campaigns` (resource)                           | `admin.campaigns.*`                   | `campaigns.manage`                                                    |
| `/campaigns/{campaign}/send` (POST)               | `admin.campaigns.send`                | `campaigns.send`                                                      |
| `/message-templates` (resource)                   | `admin.templates.*`                   | `campaigns.manage`                                                    |
| `/sms/balance`                                    | `admin.sms.balance`                   | `campaigns.manage` — BulkSMSBD balance check                          |
| **Reports**                                       |                                       |                                                                       |
| `/reports`                                        | `admin.reports.index`                 | `reports.view`                                                        |
| `/reports/{report}`                               | `admin.reports.show`                  | `reports.view`                                                        |
| `/reports/{report}/export` (POST)                 | `admin.reports.export`                | `reports.export` · `throttle:10,1`                                    |
| **System**                                        |                                       |                                                                       |
| `/settings/{group}`                               | `admin.settings.*`                    | `settings.manage`                                                     |
| `/users` (resource)                               | `admin.users.*`                       | `users.manage`                                                        |
| `/roles` (resource)                               | `admin.roles.*`                       | `roles.manage`                                                        |
| `/audit-logs`                                     | `admin.audit.index`                   | `audit.view`                                                          |

---

## 4. API — `routes/api.php` (Phase 8)

Architecture only in this build. Versioned from the first endpoint so a mobile app never has to break.

```
/api/v1/auth/{login,logout,refresh,me}
/api/v1/members            (index, show)       — privacy rules identical to web
/api/v1/directory/search
/api/v1/events             (index, show, register)
/api/v1/batches            (index, show)
/api/v1/posts              (index, show, store)
/api/v1/notifications      (index, read)
/api/v1/me/{profile,card,events,payments}
```

Auth via Laravel Sanctum personal access tokens. Responses are the **same API Resource classes** the web surface uses, which is why privacy enforcement cannot drift between web and API. See [10-api.md](10-api.md).

---

## 5. Rate limiting summary

| Scope                                       | Limit              | Why                                             |
| ------------------------------------------- | ------------------ | ----------------------------------------------- |
| `login`                                     | 5/min per email+IP | existing Fortify config                         |
| `two-factor`                                | 5/min              | existing                                        |
| `passkeys`                                  | 10/min             | existing                                        |
| Registration steps                          | 20/min             | draft churn is legitimate                       |
| Registration submit                         | 5/min              | prevents bulk fake members                      |
| Public forms (contact, donate, sponsorship) | 6/min              | spam                                            |
| Member verification lookup                  | 30/min             | QR scanning is bursty but ULIDs are unguessable |
| Post / comment / report creation            | 10–20/min          | community spam                                  |
| Event check-in scan                         | 120/min            | a volunteer scanning a queue at the gate        |
| Report export                               | 10/min             | exports are expensive                           |
| Global API                                  | 60/min per token   |                                                 |
