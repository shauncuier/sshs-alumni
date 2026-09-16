# 17 — সুবর্ণজয়ন্তী ২০২৬ / Golden Jubilee 2026

> **৫০ বছরের গৌরবময় পথচলা**
>
> ১৯৭৬ — ২০২৬

## 0. Whose fifty years

| | |
|---|---|
| **Celebrated** | সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয় / Sabuj Shikshayatan Government High School — **established 1976**, EIIN 105070, Sitakunda, Chattogram |
| **Organised by** | প্রাক্তন ছাত্র-ছাত্রী পরিষদ / Former Students Association — **established 2015** |

**The fifty years are the school's.** The association is eleven years old in 2026 and is the body running the celebration. Copy across the site reflects this exactly:

- Hero credits the school's milestone — *সবুজ শিক্ষায়তন-এর ৫০ বছর*
- The association is named as organiser, not as the subject of the anniversary
- The school-history timeline shows ১৯৭৬ (school founded) **and** ২০১৫ (association founded)
- The About page tells the association's own eleven-year story separately

Getting this wrong would misstate the history of both organizations. See [00-overview.md §3](00-overview.md) for the verified source data.

---

## 1. The core design decision

The Golden Jubilee is **not** a special subsystem. It is one row in `events` with `is_flagship = true`, plus a `jubilee` settings group for microsite copy.

**Why this matters.** A dedicated Jubilee module would be dead code on 1 January 2027, and the association would be back where it started for its next reunion. Modelling it as the first flagship event means every improvement made for the Jubilee — ticketing, QR check-in, sponsor walls, countdowns, schedules — is immediately reusable for the next fifty years of events.

What the Jubilee gets that an ordinary event does not:

| Element | Mechanism |
|---|---|
| Dedicated URLs (`/jubilee/*`) | routes resolving the flagship event |
| Distinct visual treatment | the "ceremonial" register — see [07-branding-ui.md §4](07-branding-ui.md) |
| Microsite-only copy | `settings.jubilee.*` |
| Prominence on the home page | `is_flagship` |

Everything else — registration, tickets, payments, check-in, gallery, sponsors, volunteers — is the general event system.

---

## 2. The date rule

### The final date has not been decided by the committee.

Until an administrator publishes it, every public surface renders:

> **তারিখ শীঘ্রই ঘোষণা করা হবে**

### Enforced structurally

```php
// events table
$table->string('date_status', 16)->default('tba')->index();  // tba | announced
$table->timestamp('starts_at')->nullable()->index();
$table->timestamp('ends_at')->nullable();
```

```tsx
{event.date_status === 'announced'
    ? <DateDisplay from={event.starts_at} to={event.ends_at} />
    : <p className="jubilee-tba">{t('jubilee.date_tba')}</p>}

{event.date_status === 'announced' && <JubileeCountdown to={event.starts_at} />}
```

```php
// lang/bn/jubilee.php
'date_tba' => 'তারিখ শীঘ্রই ঘোষণা করা হবে',

// lang/en/jubilee.php
'date_tba' => 'The date will be announced soon',
```

### Rules

1. **No Golden Jubilee date literal exists anywhere in the codebase** — not in a migration, a seeder, a config file, an env variable or a component.
2. `JubileeSeeder` creates the event with `date_status = 'tba'` and `starts_at = null`.
3. The countdown component does not render while the date is TBA — it is not hidden with CSS, it is not rendered at all.
4. Event reminders are scheduled relative to `starts_at` and therefore cannot fire while it is null.
5. Tests assert the Bangla TBA string renders and that no countdown appears. See [14-testing.md](14-testing.md).

### Publishing the date

`/admin/jubilee` → **Announce Date**, requires `events.publish`.

Setting the date:

1. Sets `starts_at`, optionally `ends_at`.
2. Sets `date_status = 'announced'`.
3. Writes an audit row.
4. Optionally dispatches an announcement to all members across their chosen channels.
5. Makes the countdown appear everywhere it belongs, with no deployment.

The date can be changed again afterwards, and reverted to `tba` — the committee's plans are allowed to change.

---

## 3. Microsite

| Route | Page |
|---|---|
| `/jubilee` | landing — hero, theme, 1976–2026 milestone, overview, countdown or TBA, CTAs |
| `/jubilee/schedule` | programme schedule, per-session; "to be announced" while empty |
| `/jubilee/sponsors` | sponsor wall by tier |
| `/jubilee/faq` | FAQs from the `jubilee` group |
| `/jubilee/register` | registration, routed into the general event registration flow |

Also surfaced through the general system: `/events/{jubilee-slug}`, `/gallery` albums tied to the event, `/announcements` with the Jubilee audience.

### Landing page composition

```
┌──────────────────────────────────────────────┐
│  HERO — green-900 → green-800, gold type      │
│    ৫০ বছরের গৌরবময় পথচলা                      │
│    সুবর্ণজয়ন্তী ২০২৬                            │
│    সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়            │
│    ১৯৭৬ ——————————————— ২০২৬                  │
│    আয়োজনে: প্রাক্তন ছাত্র-ছাত্রী পরিষদ            │
│    [ তারিখ শীঘ্রই ঘোষণা করা হবে ]               │
│    [ নিবন্ধন করুন ]  [ বিস্তারিত ]              │
├──────────────────────────────────────────────┤
│  Countdown        (only when announced)       │
│  About the celebration                        │
│  Milestone timeline 1976 → 2026               │
│  Programme schedule (or "to be announced")    │
│  Register CTA                                 │
│  Sponsors by tier                             │
│  Organizers & committees                      │
│  Gallery preview                              │
│  FAQ                                          │
│  Contact                                      │
└──────────────────────────────────────────────┘
```

Bangla is primary throughout. English appears as a secondary line, not a replacement.

---

## 4. Settings — `jubilee` group

Editable at `/admin/jubilee` under `settings.manage`:

| Key | Default |
|---|---|
| `theme_line_bn` | ৫০ বছরের গৌরবময় পথচলা |
| `theme_line_en` | Fifty Glorious Years |
| `title_bn` | সুবর্ণজয়ন্তী ২০২৬ |
| `title_en` | Golden Jubilee 2026 |
| `from_year` | 1976 |
| `to_year` | 2026 |
| `hero_image` | `public/brand/jubilee-banner.jpg` |
| `show_countdown` | `true` — still requires `date_status = announced` |
| `organizer_block_bn` / `_en` | |
| `registration_note_bn` / `_en` | |
| `contact_phone`, `contact_email` | |

---

## 5. Event-day operations

The parts that are hard to fix on the day, and are therefore designed for now:

### Check-in

- Volunteers hold `events.checkin` — they can run the gate without being able to edit the event.
- The scanner screen is full-screen with large tap targets: it is used one-handed, standing, in a queue.
- **Duplicate check-ins are prevented by a `UNIQUE` database constraint**, not an application `if`. A second scan reports "already checked in at HH:MM by {operator}".
- Every check-in records the operator — accountability when a dispute arises at the gate.
- Rate limit 120/min, because a gate queue is bursty.

### Capacity

Registration beyond capacity produces a **waitlist** entry, not a rejection. Turning people away automatically is the wrong default for an alumni reunion.

### Offline reality

Money will be collected in cash and by mobile financial services settled outside the platform. `ManualGateway` makes that a first-class flow with a receipt number, an audit row and a CRM timeline entry — not a workaround. See [09-payments.md §3](09-payments.md).

### Before the day

- Load-test the check-in endpoint.
- Confirm the queue worker and scheduler are running.
- Confirm SMS balance and `SMS_DAILY_CAP`.
- Complete a backup restore drill.
- Print a fallback attendee list — **the venue's network will fail at the worst moment.**

---

## 6. School history

`school_milestones` is seeded with two verified entries and left open:

| Year | Title |
|---|---|
| ১৯৭৬ | বিদ্যালয়ের পথচলা শুরু — *School journey begins* |
| ২০১৫ | প্রাক্তন ছাত্র-ছাত্রী পরিষদ প্রতিষ্ঠা — *Former Students Association founded* |

Admins add unlimited milestones (year, title, description, image, order) at `/admin/school-history`. The timeline renders on `/about/school` and on the Jubilee landing page, anchored by 1976 → 2026.

---

## 7. After the Jubilee

The reason the platform is built this way.

1. Set the event `status = completed`.
2. Publish the event gallery.
3. Convert attendees who are not yet members into members through the CRM pipeline.
4. Archive the Jubilee announcements.
5. **`/jubilee` continues to work** as a permanent record of the 50th anniversary.
6. The next event is created through the same general event system, with the Jubilee's tickets, check-in, sponsors and schedule machinery already proven.

`is_flagship` can move to a future event — the microsite treatment follows the flag, not the Jubilee.

Nothing has to be deleted, rewritten or migrated. That is the whole point.
