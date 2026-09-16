# 02 — Database Schema & ERD

Target: **SQLite in development, MySQL 8 in production.** Every rule in [01-architecture.md §8](01-architecture.md) applies to every migration in this document.

## 0. Conventions

| Convention | Rule |
|---|---|
| Primary key | `id` — `bigIncrements`. Never exposed publicly. |
| Public key | `ulid` — `char(26)`, unique, indexed. Used in URLs and QR payloads. |
| Timestamps | `created_at`, `updated_at` on every table. |
| Soft deletes | `deleted_at` on user-visible, recoverable records. **Not** on ledger or audit tables. |
| Enums | `string` column with explicit length + PHP backed enum cast. Never SQL `ENUM`. |
| Booleans | `boolean`, explicit default. |
| Money | `decimal(12, 2)` + separate `currency` `char(3)` default `BDT`. Never float. |
| Foreign keys | Always declared. `restrictOnDelete` for money/audit, `nullOnDelete` for optional links, `cascadeOnDelete` only for owned child rows. |
| Bilingual text | Paired columns: `title` / `title_bn`. See [06-localization.md](06-localization.md). |
| Indexes | Explicit, named, on every column used in `WHERE`, `ORDER BY` or `JOIN`. |
| JSON | `json` column type. Never queried with a JSON path in `WHERE` (portability). |

## 1. Deviations from the original specification

The specification's table list is a sketch, and the same document also states *"Do not create redundant tables unnecessarily"* and *"Avoid duplicated concepts."* Five consolidations follow from that instruction. Each is recorded here so the decision is reviewable rather than silent.

### 1.1 `member_profiles`, `member_contacts` → folded into `members`

**Spec listed:** `members`, `member_profiles`, `member_education`, `member_employment`, `member_contacts`, `member_privacy`.

**Built:** `members` (core + academic + professional + location + contact), plus `member_privacy` (1:1), `member_links`, `member_education`, `member_employment` (all 1:N).

**Reason.** A 1:1 split across five tables is over-normalization: every directory row, every profile view and every export becomes a five-way join, and every write becomes a five-table transaction. Splitting is justified when data *repeats* (education entries, employment history, social links) or is a *separately queried concern* (privacy flags are read without the rest of the profile when filtering the directory). `member_profiles` and `member_contacts` are neither — they are one row per member, always read together with the member.

### 1.2 `crm_notes` → folded into `crm_activities`

**Spec listed:** `crm_activities`, `crm_notes`, `crm_followups` separately.

**Built:** one `crm_activities` table with a `type` column (`note` | `call` | `email` | `meeting` | `task` | `system`).

**Reason.** Notes, calls, emails and meetings have an identical shape — actor, subject, body, timestamp. A single polymorphic table is what makes the section 12 requirement (one chronological activity timeline per contact) a single ordered query instead of a four-way union. Follow-ups are `crm_tasks`, which genuinely differ (due date, assignee, completion state).

### 1.3 `event_attendance` → folded into `event_checkins`

**Spec listed:** `event_tickets`, `event_attendance`, `event_checkins`.

**Built:** `event_ticket_types` (the definition of a ticket) and `event_checkins` (the fact of attendance).

**Reason.** Attendance *is* a check-in record; two tables would always hold the same rows. More importantly, section 28 requires duplicate check-ins to be prevented — a `UNIQUE` constraint on `event_checkins.registration_id` is the mechanism, and it only works if there is exactly one table.

### 1.4 `invoices`, `receipts` → folded into `payments`

**Spec listed:** `payments`, `payment_items`, `invoices`, `receipts`.

**Built:** `payments` with `receipt_no`, `invoice_no` and on-demand PDF rendering.

**Reason.** Both tables would only ever hold a sequential number and a foreign key back to the payment. The documents themselves are rendered from the payment record at request time, so storing them adds a sync problem with no benefit. `payment_items` is also dropped — a payment has exactly one polymorphic `payable`; line-item splitting is not a requirement anywhere in the specification.

### 1.5 `notices` → folded into `announcements`

**Spec listed:** `announcements` and `notices` as separate content types.

**Built:** `announcements` with a `kind` column (`announcement` | `notice`).

**Reason.** Identical shape, identical admin UI, identical public rendering. A column distinguishes them; a table would duplicate seven fields and two controllers.

### 1.6 `batch_members` → replaced by `members.batch_id`

**Spec listed:** `batches`, `batch_members`.

**Built:** `members.batch_id` (the single source of truth) plus `batch_coordinators` (the pivot that genuinely needs to exist).

**Reason.** A member belongs to exactly one SSC batch. A pivot table would allow the pivot and the member row to disagree, and the directory's batch filter would need a join on every query. Coordinators *are* a many-to-many relationship and keep their pivot.

---

## 2. ERD — entity relationships

```
                            ┌──────────┐
                            │  users   │ (login identity)
                            └────┬─────┘
                       1:1 ·nullable both ways
                            ┌────┴─────┐
        ┌───────────────────│ members  │───────────────────┐
        │                   └────┬─────┘                   │
        │        ┌───────────────┼───────────────┐         │
        │        │               │               │         │
   ┌────┴────┐ ┌─┴──────────┐ ┌──┴───────────┐ ┌─┴──────┐ ┌┴─────────────┐
   │ batches │ │member_     │ │member_       │ │member_ │ │member_       │
   │         │ │privacy 1:1 │ │education 1:N │ │links   │ │verifications │
   └────┬────┘ └────────────┘ └──────────────┘ └────────┘ └──────────────┘
        │                                      member_employment 1:N
   ┌────┴──────────────┐
   │batch_coordinators │ (pivot: batches ↔ members)
   └───────────────────┘

   members ──1:N──> event_registrations ──1:1──> event_checkins
        │                   │
        │              ┌────┴─────┐
        │              │  events  │──1:N──> event_ticket_types
        │              └────┬─────┘         event_registration_guests
        │                   │
        │                   └──1:N──> sponsors, donations, volunteer_assignments
        │
        ├──1:N──> membership_fees ──┐
        ├──1:N──> donations ────────┤
        ├──0:1──> crm_contacts      ├──morph "payable"──> payments
        │              │            │
        │              │       sponsors ──┘
        │              │
        │        ┌─────┴────────────────────┐
        │        │ crm_activities (morph)   │  subject = member | crm_contact
        │        │ crm_tasks     (morph)    │
        │        │ crm_taggables (morph)    │──> crm_tags
        │        └──────────────────────────┘
        │
        ├──1:N──> posts ──1:N──> comments (morph) , reactions (morph)
        ├──1:N──> alumni_stories
        ├──0:1──> volunteers ──1:N──> volunteer_assignments ──> volunteer_teams
        └──0:N──> committee_members ──> committees

   media (morph)          ──> attaches to anything
   audit_logs (morph)     ──> records who did what to which entity
   content_reports (morph)──> posts | comments | alumni_stories
   campaigns ──1:N──> campaign_recipients ──> members | crm_contacts
   settings, message_templates, school_milestones, faqs, pages,
   news, announcements, gallery_albums ──1:N──> gallery_images
```

---

## 3. Identity & access

### `users` *(extends the existing starter-kit table)*

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `name` | string(191) | existing |
| `email` | string(191) unique | existing |
| `email_verified_at` | timestamp null | existing |
| `password` | string | existing, hashed |
| `two_factor_secret` | text null | existing |
| `two_factor_recovery_codes` | text null | existing |
| `two_factor_confirmed_at` | timestamp null | existing |
| `remember_token` | string(100) null | existing |
| **`locale`** | string(5) default `bn` | added — UI language preference |
| **`avatar_path`** | string(255) null | added |
| **`phone`** | string(32) null, index | added |
| **`phone_verified_at`** | timestamp null | added — architecture for future phone verification |
| **`status`** | string(24) default `active` | added — `active`\|`suspended`\|`disabled` |
| **`last_active_at`** | timestamp null, index | added — engagement tracking |
| `deleted_at` | timestamp null | added |

Existing `passkeys` table and the Fortify two-factor columns are untouched.

### spatie/laravel-permission tables

`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — published from the package, unmodified. See [04-roles-permissions.md](04-roles-permissions.md).

---

## 4. Alumni core

### `members` — the canonical alumni record

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `ulid` | char(26) unique | public identifier — URLs, QR codes |
| `user_id` | FK users null, unique, nullOnDelete | a member may have no login |
| `membership_no` | string(32) null, unique | assigned on approval |
| **Identity** | | |
| `full_name` | string(150), index | |
| `full_name_bn` | string(150) null, index | |
| `photo_path` | string(255) null | |
| `date_of_birth` | date null | |
| `gender` | string(16) null | enum cast |
| `blood_group` | string(8) null, index | enum cast — feeds future blood-donor directory |
| **Relationship to school** | | |
| `relation_type` | string(32), index | `former_student`\|`former_teacher`\|`current_teacher`\|`staff`\|`guardian`\|`supporter`\|`other` |
| **Academic** | | |
| `batch_id` | FK batches null, index, nullOnDelete | |
| `ssc_year` | smallInteger null, index | |
| `student_id` | string(32) null | school roll, if known |
| `admission_year` | smallInteger null | |
| `group_stream` | string(32) null | Science / Humanities / Business |
| `section` | string(16) null | |
| `house` | string(32) null | |
| `higher_education` | string(255) null | free-text summary; detail rows in `member_education` |
| **Professional** | | |
| `occupation` | string(120) null, index | |
| `organization` | string(150) null, index | |
| `job_title` | string(120) null | |
| `industry` | string(80) null, index | |
| `business_info` | text null | |
| **Location** | | |
| `country` | string(80) null default `Bangladesh`, index | |
| `division` | string(80) null, index | |
| `district` | string(80) null, index | |
| `city` | string(80) null, index | |
| `address` | text null | |
| **Contact** | | |
| `mobile` | string(32) null, index | |
| `whatsapp` | string(32) null | |
| `email` | string(191) null, index | may differ from `users.email` |
| `emergency_contact_name` | string(120) null | |
| `emergency_contact_phone` | string(32) null | |
| **Profile** | | |
| `bio` | text null | |
| `bio_bn` | text null | |
| `skills` | json null | array of strings |
| `interests` | json null | array of strings |
| **Membership state** | | |
| `status` | string(24) default `pending`, index | `pending`\|`under_review`\|`approved`\|`rejected`\|`suspended`\|`archived` |
| `verified_at` | timestamp null | |
| `verified_by` | FK users null, nullOnDelete | |
| `registered_at` | timestamp null | |
| `profile_completion` | tinyInteger default 0 | 0–100, recalculated by observer |
| `is_featured` | boolean default false | featured alumni on the home page |
| **Search** | | |
| `search_blob` | string(500) null, index | lowercase name + bn name + org + occupation + city |
| `created_at` / `updated_at` / `deleted_at` | | soft deletes |

**Composite indexes:** `(status, batch_id)`, `(status, district)`, `(relation_type, status)`.

**Why `user_id` is nullable both ways.** The association needs to enter a 1985 graduate from a paper register who will never log in; and an office staff member needs a login with no alumni record. Forcing a 1:1 in either direction would break one of those.

### `member_privacy` — 1:1 with `members`

| Column | Type | Default |
|---|---|---|
| `member_id` | FK members, unique, cascadeOnDelete | |
| `show_profile` | boolean | `true` |
| `show_phone` | boolean | `false` |
| `show_email` | boolean | `false` |
| `show_workplace` | boolean | `true` |
| `show_location` | boolean | `true` |
| `show_date_of_birth` | boolean | `false` |
| `show_in_batch_list` | boolean | `true` |

Defaults are privacy-preserving: contact details are hidden unless the member opts in. See [08-security-privacy.md](08-security-privacy.md).

### `member_verifications` — status transition history

| Column | Type | Notes |
|---|---|---|
| `id`, `member_id` FK cascadeOnDelete, index | | |
| `actor_id` | FK users null, nullOnDelete | who acted |
| `from_status` | string(24) null | |
| `to_status` | string(24) | |
| `note` | text null | internal note, never shown to the member |
| `correction_requested` | text null | message sent to the member |
| `created_at` | timestamp | append-only, no `updated_at`, no soft delete |

### `member_links`

`id`, `member_id` FK cascade, `type` string(24) (`linkedin`|`facebook`|`website`|`x`|`youtube`|`other`), `url` string(255), `display_order` smallInteger. Unique `(member_id, type, url)`.

### `member_education`

`id`, `member_id` FK cascade, `institution` string(150), `degree` string(120) null, `field_of_study` string(120) null, `start_year` smallInteger null, `end_year` smallInteger null, `display_order`.

### `member_employment`

`id`, `member_id` FK cascade, `organization` string(150), `job_title` string(120) null, `industry` string(80) null, `start_date` date null, `end_date` date null, `is_current` boolean default false, `display_order`.

`members.occupation` / `organization` / `industry` hold the **current** values, denormalized for directory filtering. `member_employment` holds the history. The observer keeps them in sync when `is_current` changes.

---

## 5. Batches

### `batches`

| Column | Type | Notes |
|---|---|---|
| `id`, `slug` string(80) unique | | e.g. `ssc-1990` |
| `name` | string(80) | `SSC 1990` |
| `name_bn` | string(80) null | `এসএসসি ১৯৯০` |
| `ssc_year` | smallInteger unique, index | the natural key |
| `description` / `description_bn` | text null | |
| `cover_path` | string(255) null | |
| `members_count` | integer default 0 | counter cache, maintained by `MemberObserver` |
| `status` | string(16) default `active` | `active`\|`archived` |
| timestamps, `deleted_at` | | |

### `batch_coordinators` — pivot

`id`, `batch_id` FK cascade, `member_id` FK cascade, `assigned_at` timestamp, `assigned_by` FK users null. Unique `(batch_id, member_id)`.

Grants the `Batch Coordinator` role's scoped permissions. See [04-roles-permissions.md](04-roles-permissions.md).

---

## 6. Events

### `events`

| Column | Type | Notes |
|---|---|---|
| `id`, `ulid` char(26) unique | | |
| `slug` | string(120) unique | |
| `type` | string(32), index | `reunion`\|`seminar`\|`sports`\|`cultural`\|`fundraising`\|`meeting`\|`volunteer`\|`general` |
| `title` / `title_bn` | string(200) | |
| `summary` / `summary_bn` | string(500) null | |
| `description` / `description_bn` | longText null | |
| `cover_path` | string(255) null | |
| **`date_status`** | string(16) default `tba`, index | **`tba`** \| `announced` |
| **`starts_at`** | timestamp **null**, index | null while `date_status = tba` |
| **`ends_at`** | timestamp null | |
| `venue` / `venue_bn` | string(200) null | |
| `address` | text null | |
| `map_url` | string(500) null | |
| `latitude` / `longitude` | decimal(10,7) null | |
| `registration_required` | boolean default false | |
| `registration_opens_at` | timestamp null | |
| `registration_closes_at` | timestamp null | |
| `capacity` | integer null | null = unlimited |
| `registration_fee` | decimal(12,2) null | |
| `currency` | char(3) default `BDT` | |
| `organizer_name` | string(150) null | |
| `contact_phone` / `contact_email` | string | |
| `status` | string(32) default `draft`, index | `draft`\|`published`\|`registration_open`\|`registration_closed`\|`completed`\|`cancelled` |
| **`is_flagship`** | boolean default false, index | the Golden Jubilee row |
| `meta_title`, `meta_description`, `og_image_path` | | SEO — see [08](08-security-privacy.md) and the SEO section of [05-modules.md](05-modules.md) |
| `published_at` | timestamp null | |
| `created_by` | FK users null | |
| timestamps, `deleted_at` | | |

**The date rule is structural.** `starts_at` is nullable and `date_status` defaults to `tba`. No Golden Jubilee date literal exists anywhere in the codebase. See [17-golden-jubilee.md](17-golden-jubilee.md).

### `event_ticket_types`

`id`, `event_id` FK cascade index, `name` string(80), `name_bn` string(80) null, `description` text null, `price` decimal(12,2) default 0, `currency` char(3), `quantity` integer null, `sold_count` integer default 0, `per_person_limit` tinyInteger default 1, `is_active` boolean default true, `display_order`.

### `event_registrations`

| Column | Type | Notes |
|---|---|---|
| `id`, `ulid` char(26) unique | | the ticket reference |
| `event_id` | FK events cascade, index | |
| `member_id` | FK members null, nullOnDelete, index | |
| `crm_contact_id` | FK crm_contacts null, nullOnDelete | non-member guest |
| `ticket_type_id` | FK event_ticket_types null, nullOnDelete | |
| `registrant_name` | string(150) | snapshot — survives member deletion |
| `registrant_email` | string(191) null | |
| `registrant_phone` | string(32) null | |
| `guests_count` | tinyInteger default 0 | |
| `amount_due` | decimal(12,2) default 0 | |
| `currency` | char(3) default `BDT` | |
| `payment_status` | string(16) default `pending`, index | mirrors the linked payment |
| `status` | string(16) default `confirmed`, index | `confirmed`\|`waitlisted`\|`cancelled` |
| **`qr_token`** | char(40) unique | random, not derived from the id |
| `registered_at` | timestamp | |
| `notes` | text null | |
| timestamps, `deleted_at` | | |

Unique `(event_id, member_id)` where `member_id` is not null — prevents double registration.

### `event_registration_guests`

`id`, `event_registration_id` FK cascade, `name` string(120), `relation` string(60) null, `age_group` string(20) null (`adult`|`child`), `notes`.

### `event_checkins`

| Column | Type | Notes |
|---|---|---|
| `id` | | |
| **`event_registration_id`** | FK cascade, **UNIQUE** | **this constraint is the duplicate-check-in prevention** |
| `event_id` | FK cascade, index | denormalized for per-event attendance queries |
| `checked_in_at` | timestamp, index | |
| `operator_id` | FK users, restrictOnDelete | who scanned — required for accountability |
| `gate` | string(60) null | |
| `device` | string(120) null | |
| `created_at` | timestamp | append-only, no soft delete |

---

## 7. CRM

### `crm_contacts`

| Column | Type | Notes |
|---|---|---|
| `id`, `ulid` char(26) unique | | |
| **`member_id`** | FK members null, nullOnDelete, index | **links to the alumni record instead of duplicating the person** |
| `type` | string(24), index | `prospect`\|`volunteer`\|`donor`\|`sponsor`\|`guest`\|`partner`\|`organization`\|`other` |
| `name` / `name_bn` | string(150) | |
| `organization_name` | string(150) null, index | |
| `designation` | string(120) null | |
| `email` | string(191) null, index | |
| `phone` / `whatsapp` | string(32) null, index | |
| `address` | text null | |
| `city` / `district` / `country` | string(80) null | |
| `source` | string(80) null, index | how they reached the association |
| `relationship_type` | string(80) null | |
| `pipeline_status` | string(24) default `new`, index | `new`\|`contacted`\|`interested`\|`registered`\|`verified`\|`active`\|`inactive`\|`archived` |
| `owner_id` | FK users null, nullOnDelete, index | assigned staff |
| `last_activity_at` | timestamp null, index | |
| `notes` | text null | |
| timestamps, `deleted_at` | | |

### `crm_activities` — the polymorphic timeline

| Column | Type | Notes |
|---|---|---|
| `id` | | |
| `subject_type` / `subject_id` | morph, index | `Member` **or** `CrmContact` |
| `type` | string(16), index | `note`\|`call`\|`email`\|`meeting`\|`task`\|`system` |
| `subject_line` | string(200) null | |
| `body` | text null | |
| `outcome` | string(80) null | |
| `occurred_at` | timestamp, index | |
| `user_id` | FK users null, nullOnDelete | actor; null for `system` |
| `meta` | json null | channel-specific detail |
| timestamps | | |

This one table renders the section 12 timeline example — *registration submitted → admin contacted → identity verified → Jubilee registration → donation made → volunteer assigned* — as a single ordered query, with `system` rows written automatically by observers on verification, payment and registration events.

### `crm_tasks`

`id`, morph `subject` (index), `title` string(200), `description` text null, `due_at` timestamp null index, `status` string(16) default `open` (`open`|`in_progress`|`done`|`cancelled`), `priority` string(12) default `normal` (`low`|`normal`|`high`|`urgent`), `assigned_to` FK users null index, `created_by` FK users null, `completed_at` timestamp null, timestamps, `deleted_at`.

### `crm_tags` / `crm_taggables`

- `crm_tags`: `id`, `name` string(60) unique, `name_bn` string(60) null, `color` string(16) null, `description`.
- `crm_taggables`: `crm_tag_id` FK cascade, morph `taggable` (members and contacts alike). Unique `(crm_tag_id, taggable_type, taggable_id)`.

---

## 8. Payments & fundraising

### `payments` — the single ledger

| Column | Type | Notes |
|---|---|---|
| `id`, `ulid` char(26) unique | | |
| `reference` | string(40) unique, index | human-quotable reference |
| **`payable_type` / `payable_id`** | morph, index | `MembershipFee` \| `EventRegistration` \| `Donation` \| `Sponsor` |
| `payer_member_id` | FK members null, nullOnDelete, index | |
| `crm_contact_id` | FK crm_contacts null, nullOnDelete | |
| `payer_name` | string(150) | snapshot |
| `gateway` | string(32) default `manual`, index | `manual` today; bKash/Nagad/SSLCommerz later |
| `gateway_txn_id` | string(120) null, index | |
| `amount` | decimal(12,2) | |
| `currency` | char(3) default `BDT` | |
| `status` | string(16) default `pending`, index | `pending`\|`paid`\|`failed`\|`cancelled`\|`refunded` |
| `paid_at` | timestamp null, index | |
| `receipt_no` | string(40) null, unique | issued on `paid` |
| `invoice_no` | string(40) null, unique | issued for sponsorships |
| `recorded_by` | FK users null, restrictOnDelete | set for offline/manual entry |
| `refunded_at` | timestamp null | |
| `refund_reason` | text null | |
| `notes` | text null | |
| `meta` | json null | gateway payload |
| timestamps | | **no soft delete — this is a financial ledger** |

Composite index `(status, paid_at)` for the finance dashboard.

### `membership_fees` *(payable)*

`id`, `member_id` FK cascade index, `period_label` string(40) (`2026`, `Life`), `amount` decimal(12,2), `currency`, `due_at` date null index, `status` string(16) default `pending` index, `waived` boolean default false, `waived_reason`, timestamps. Unique `(member_id, period_label)`.

### `donations` *(payable)*

`id`, `ulid`, `donor_member_id` FK null index, `crm_contact_id` FK null, `donor_name` string(150), `donor_email`, `donor_phone`, `campaign` string(120) null index, `event_id` FK events null, `amount` decimal(12,2), `currency`, `is_anonymous` boolean default false, `message` text null, `message_bn`, `status` string(16) default `pending` index, `is_public` boolean default true, `received_at` timestamp null, timestamps, `deleted_at`.

### `sponsorship_packages`

`id`, `name` string(80), `name_bn`, `slug` unique, `tier` string(24) index (`title`|`platinum`|`gold`|`silver`|`partner`|`custom`), `amount` decimal(12,2) null, `currency`, `benefits` text null, `benefits_bn`, `max_slots` smallInteger null, `display_order`, `is_active` boolean default true, timestamps.

### `sponsors` *(payable)*

`id`, `ulid`, `event_id` FK null index, `sponsorship_package_id` FK null, `kind` string(16) (`individual`|`company`), `name` string(150), `name_bn`, `contact_name`, `contact_email`, `contact_phone`, `logo_path` string(255) null, `website` string(255) null, `amount` decimal(12,2) null, `currency`, `agreement_path` string(255) null *(private disk)*, `status` string(16) default `pending` index (`pending`|`confirmed`|`paid`|`cancelled`), `is_public` boolean default false, `display_order`, `notes` text null, timestamps, `deleted_at`.

`agreement_path` points at the **private** disk and is never served directly. See [08-security-privacy.md](08-security-privacy.md).

---

## 9. Volunteers

### `volunteer_teams`

`id`, `slug` unique, `name` string(80), `name_bn`, `description`, `description_bn`, `lead_member_id` FK members null, `display_order`, `is_active`, timestamps.

Seeded teams: Registration, Reception, Guest Management, Media, Photography, Logistics, Finance, Technical, Hospitality.

### `volunteers`

`id`, `member_id` FK null index, `crm_contact_id` FK null, `name` string(150) snapshot, `phone`, `email`, `skills` json null, `availability` string(120) null, `location` string(120) null, `status` string(16) default `applied` index (`applied`|`approved`|`active`|`inactive`|`rejected`), `notes`, `applied_at`, timestamps, `deleted_at`. Unique `(member_id)` where not null.

### `volunteer_assignments`

`id`, `volunteer_id` FK cascade index, `volunteer_team_id` FK null index, `event_id` FK events null index, `responsibility` string(200) null, `shift_start` timestamp null, `shift_end` timestamp null, `status` string(16) default `assigned` (`assigned`|`confirmed`|`completed`|`cancelled`), `assigned_by` FK users null, timestamps.

---

## 10. Committees

### `committees`

`id`, `slug` unique, `name` string(120), `name_bn`, `type` string(32) index (`executive`|`organizing`|`event`|`finance`|`media`|`volunteer`|`batch`), `description`, `description_bn`, `term_start` date null, `term_end` date null, `status` string(16) default `active` index, `display_order`, timestamps, `deleted_at`.

### `committee_members`

`id`, `committee_id` FK cascade index, `member_id` FK members null nullOnDelete, `name` string(150) *(used when the person is not a platform member)*, `name_bn`, `role` string(80), `designation` string(120) null, `designation_bn`, `photo_path`, `bio` text null, `bio_bn`, `contact_email`, `contact_phone`, `display_order`, `start_date` date null, `end_date` date null, `status` string(16) default `active`, timestamps.

`member_id` nullable because historic committee members may predate the platform.

---

## 11. Community

### `posts`

`id`, `ulid`, `author_member_id` FK members cascade index, `category` string(40) index (`general`|`reunion`|`batch`|`memories`|`career`|`business`|`support`|`volunteer`|`jubilee`), `batch_id` FK batches null index, `title` string(200) null, `body` text, `status` string(16) default `published` index (`published`|`hidden`|`removed`), `is_pinned` boolean default false, `comments_enabled` boolean default true, `comments_count` integer default 0, `reactions_count` integer default 0, `last_activity_at` timestamp index, timestamps, `deleted_at`.

Composite index `(status, category, last_activity_at)`.

### `comments`

`id`, morph `commentable` (posts, alumni_stories, news), `author_member_id` FK cascade, `parent_id` self-FK null cascade, `body` text, `status` string(16) default `published` index, timestamps, `deleted_at`.

### `reactions`

`id`, morph `reactable`, `member_id` FK cascade, `type` string(20) default `like`, `created_at`. **Unique `(member_id, reactable_type, reactable_id)`** — one reaction per member per item.

### `content_reports`

`id`, morph `reportable`, `reporter_member_id` FK null, `reason` string(40) index (`spam`|`abuse`|`false_info`|`harassment`|`other`), `note` text null, `status` string(16) default `open` index (`open`|`reviewing`|`resolved`|`dismissed`), `resolved_by` FK users null, `resolved_at`, `resolution_note`, timestamps.

Mentions are **not** a table. A mention is stored inline in the post body as `@member:{ulid}` and resolved at render time — a join table would add write cost for a feature that is only ever read with the post.

---

## 12. CMS

### `pages`

`id`, `slug` unique index, `title` string(200), `title_bn`, `body` longText null, `body_bn`, `status` string(16) default `draft` index, `is_system` boolean default false *(privacy-policy and terms cannot be deleted)*, `meta_title`, `meta_description`, `og_image_path`, `published_at`, `updated_by` FK users null, timestamps, `deleted_at`.

### `news`

`id`, `slug` unique, `title` string(200), `title_bn`, `excerpt` string(500) null, `excerpt_bn`, `body` longText, `body_bn`, `cover_path`, `author_id` FK users null, `category` string(60) null index, `is_featured` boolean default false index, `status` string(16) default `draft` index, `published_at` timestamp null index, `views_count` integer default 0, SEO trio, timestamps, `deleted_at`.

### `announcements`

`id`, `kind` string(16) default `announcement` index (`announcement`|`notice`), `title` string(200), `title_bn`, `body` text, `body_bn`, `level` string(16) default `info` index (`info`|`important`|`urgent`), `audience` string(16) default `public` index (`public`|`members`|`batch`|`role`), `batch_id` FK null, `starts_at` timestamp null index, `ends_at` timestamp null index, `is_pinned` boolean default false, `attachment_path` string(255) null, `published_by` FK users null, `status` string(16) default `draft` index, timestamps, `deleted_at`.

### `gallery_albums`

`id`, `slug` unique, `title` string(200), `title_bn`, `description`, `description_bn`, `cover_path`, `event_id` FK null index, `batch_id` FK null index, `status` string(16) default `draft` index, `published_at`, `images_count` integer default 0, `display_order`, timestamps, `deleted_at`.

### `gallery_images`

`id`, `gallery_album_id` FK cascade index, `media_id` FK media cascade, `caption` string(255) null, `caption_bn`, `display_order` index, timestamps.

### `alumni_stories`

`id`, `slug` unique, `member_id` FK members null index, `author_name` string(150) *(snapshot)*, `title` string(200), `title_bn`, `body` longText, `body_bn`, `photo_path`, `batch_id` FK null index, `career_summary` string(255) null, `is_featured` boolean default false index, `status` string(16) default `pending` index (`pending`|`published`|`rejected`), `published_at`, `reviewed_by` FK users null, SEO trio, timestamps, `deleted_at`.

### `school_milestones`

`id`, `year` smallInteger index, `date_label` string(60) null, `title` string(200), `title_bn`, `description` text null, `description_bn`, `image_path`, `display_order` index, `is_highlighted` boolean default false, timestamps.

Seeded with two verified milestones and left open for the committee to extend without limit:

| Year | Title (বাংলা) | Title (English) |
|---|---|---|
| ১৯৭৬ | বিদ্যালয়ের পথচলা শুরু | School journey begins |
| ২০১৫ | প্রাক্তন ছাত্র-ছাত্রী পরিষদ প্রতিষ্ঠা | Former Students Association founded |

Seeding the association's own 2015 founding as a milestone is what keeps the timeline honest: the fifty years belong to the school, and the timeline shows where the association joins that story.

### `faqs`

`id`, `group` string(40) default `general` index (`general`|`jubilee`|`membership`|`payment`|`event`), `question` string(300), `question_bn`, `answer` text, `answer_bn`, `display_order` index, `is_published` boolean default true, timestamps.

---

## 13. Infrastructure

### `media` — polymorphic attachment store

`id`, `ulid` unique, morph `model` (nullable — supports an unattached library), `collection` string(40) index (`profile`|`gallery`|`cover`|`banner`|`logo`|`document`|`attachment`), `disk` string(20) default `public`, `path` string(500), `thumb_path` string(500) null, `original_name` string(255), `mime_type` string(120) index, `extension` string(12), `size` unsignedBigInteger, `width` / `height` integer null, `alt` string(255) null, `alt_bn`, `is_public` boolean default true index, `uploaded_by` FK users null, timestamps, `deleted_at`.

`is_public = false` routes storage to the **private** disk. Sponsor agreements and member verification documents always land there.

### `message_templates`

`id`, `key` string(80) unique, `name` string(120), `channel` string(16) index (`mail`|`sms`|`whatsapp`|`database`), `subject` string(255) null, `subject_bn`, `body` longText, `body_bn`, `variables` json null, `is_system` boolean default false, timestamps.

### `campaigns`

`id`, `name` string(150), `message_template_id` FK null, `channel` string(16) index, `subject`, `subject_bn`, `body` longText, `body_bn`, `audience_type` string(24) (`all_members`|`batch`|`status`|`role`|`custom`), `audience_filters` json null, `scheduled_at` timestamp null index, `started_at`, `completed_at`, `status` string(16) default `draft` index (`draft`|`scheduled`|`sending`|`completed`|`failed`|`cancelled`), `recipients_count`, `sent_count`, `failed_count`, `opened_count`, `created_by` FK users null, timestamps, `deleted_at`.

### `campaign_recipients`

`id`, `campaign_id` FK cascade index, `member_id` FK null, `crm_contact_id` FK null, `email` string(191) null, `phone` string(32) null, `status` string(16) default `queued` index (`queued`|`sent`|`delivered`|`failed`|`opened`|`bounced`|`unsubscribed`), `sent_at`, `delivered_at`, `opened_at`, `error` text null, `provider_message_id` string(191) null index, timestamps.

Composite index `(campaign_id, status)`.

### `notifications`

Laravel's standard table: `uuid` id, morph `notifiable`, `type`, `data` json, `read_at`. Used for in-app notifications. Mail/SMS/WhatsApp ride the same notification classes on different channels.

### `audit_logs`

| Column | Type | Notes |
|---|---|---|
| `id` | | |
| `user_id` | FK users null, nullOnDelete, index | |
| `action` | string(80), index | `member.approved`, `payment.refunded`, `role.changed`, … |
| `auditable_type` / `auditable_id` | morph, index | |
| `before` | json null | **changed attributes only**, not the whole row |
| `after` | json null | changed attributes only |
| `description` | string(500) null | |
| `ip_address` | string(45) null | IPv6-capable |
| `user_agent` | string(500) null | |
| `created_at` | timestamp, index | **append-only. No `updated_at`, no soft delete.** |

Composite index `(auditable_type, auditable_id, created_at)`.

Storing only changed attributes keeps the table from becoming a full second copy of the database, and avoids writing password hashes or tokens into an audit row.

### `settings`

`id`, `group` string(40) index (`organization`|`school`|`contact`|`social`|`registration`|`membership`|`event`|`jubilee`|`notification`|`seo`|`privacy`|`system`), `key` string(80), `value` json null, `type` string(20) default `string`, `is_public` boolean default false. Unique `(group, key)`.

The `organization` and `school` groups are **separate on purpose**: the association was established in 2015 and the school in 1976, and the platform must never conflate them. The `school` group also holds office-holder names (head teacher, সভাপতি) so they can be updated without a redeploy.

Read through `SettingsService`, cached as one array, invalidated on write. `is_public` settings are shared to the Inertia frontend; the rest never leave the server.

### Framework tables *(already present)*

`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `passkeys`.

---

## 14. Table count

| Group | Tables |
|---|---|
| Identity & access | 7 |
| Alumni core | 6 |
| Batches | 2 |
| Events | 5 |
| CRM | 5 |
| Payments & fundraising | 5 |
| Volunteers | 3 |
| Committees | 2 |
| Community | 4 |
| CMS | 8 |
| Infrastructure | 6 |
| Framework | 8 |
| **Total** | **61** |

## 15. Index strategy summary

Every column that appears in a directory filter, an admin list filter, a dashboard aggregate or a sort is indexed. The heavy read paths and their supporting indexes:

| Query | Index |
|---|---|
| Directory search | `members.search_blob` |
| Directory batch filter | `members (status, batch_id)` |
| Directory location filter | `members (status, district)` |
| Batch index page | `batches.members_count` (counter cache — no `COUNT(*)`) |
| Admin verification queue | `members.status` |
| Event registration list | `event_registrations (event_id, status)` |
| Check-in duplicate guard | `event_checkins.event_registration_id` UNIQUE |
| Finance dashboard | `payments (status, paid_at)` |
| CRM pipeline board | `crm_contacts (pipeline_status, owner_id)` |
| CRM timeline | `crm_activities (subject_type, subject_id, occurred_at)` |
| Community feed | `posts (status, category, last_activity_at)` |
| Campaign progress | `campaign_recipients (campaign_id, status)` |
| Audit trail for an entity | `audit_logs (auditable_type, auditable_id, created_at)` |
