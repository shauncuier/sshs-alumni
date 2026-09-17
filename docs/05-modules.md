# 05 — Functional Module Specification

One section per module: what it does, the rules that govern it, and what "done" means.

---

## 1. Membership registration

### Flow

Public multi-step form at `/join`. Five steps, each validated on submit, draft persisted in the session so a refresh does not lose work.

| Step                | Fields                                                                                                                        |
| ------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| **1. Basic**        | full name, name in Bangla, profile photo, date of birth, gender, blood group, mobile, WhatsApp, email, relationship to school |
| **2. Academic**     | batch (SSC year), student ID, admission year, group/stream, section, house, higher education                                  |
| **3. Professional** | occupation, organization, job title, industry, business information                                                           |
| **4. Location**     | country, division, district, city, current address, emergency contact name + phone                                            |
| **5. Review**       | bio, skills, interests, social links, **privacy controls**, review everything, submit                                         |

### Rules

- Relationship to school drives which fields are required. A `former_teacher` is not asked for an SSC year; a `former_student` is.
- A `User` account is created at submission with email verification pending. `members.user_id` links them.
- Status starts at `pending`. **No membership number is assigned until approval.**
- Privacy defaults are the safe ones: phone and email hidden, workplace and location shown. The member sees and can change every flag on step 5 before submitting.
- `profile_completion` is computed on save by `ProfileCompletionCalculator` — a weighted score over photo, contact, academic, professional, location and bio groups.
- Duplicate guard: a warning (not a hard block) when name + SSC year already exists. The committee decides; the system does not silently reject a real person.
- Rate limited. See [03-routes.md §5](03-routes.md).

### Notifications

`MemberRegistered` → to the applicant (confirmation) and to users holding `members.verify` (queue alert).

### Done when

A visitor completes all five steps, receives a confirmation email, and appears in the admin verification queue with `status = pending`.

---

## 2. Member verification

### Statuses

```
pending ──> under_review ──> approved
   │             │      └──> rejected
   │             └────────> (correction requested, stays under_review)
   └──> archived

approved ──> suspended ──> approved | archived
```

### Admin capabilities

- Review the application side by side with submitted documents.
- Add an **internal note** — never visible to the member.
- **Request a correction** — sends the member a message and keeps the record open.
- Approve → assigns a membership number, sets `verified_at` and `verified_by`, fires `MemberApproved`.
- Reject → requires a reason, which is sent to the member.
- Suspend / archive, both reversible by an admin.
- Manually assign or override a membership number.

### Membership number

`MembershipNumberGenerator` produces `SSHS-{SSC_YEAR}-{SEQ}`, e.g. `SSHS-1990-0042`, with the sequence scoped per batch. Generated inside a transaction with a row lock so two simultaneous approvals cannot collide. Manual override is allowed and audited.

### History

Every transition writes a `member_verifications` row: actor, from-status, to-status, note, timestamp. Append-only. The panel renders it as a timeline.

### Done when

An admin can move an application through every status, the member receives the right notification at each step, and the full history is visible and immutable.

---

## 3. Alumni directory

**Members-only.** Requires `auth` + `verified` + `member.approved`. The public `/batches` page shows aggregate counts only.

An admin setting (`privacy.public_directory`) can open it publicly later without a code change — the resource layer already handles the anonymous case.

### Search

One indexed `LIKE` against `members.search_blob`, which holds a lowercase concatenation of name, Bangla name, organization, occupation and city. Debounced client-side, paginated server-side.

### Filters

Batch · SSC year · district · city · country · occupation · industry · membership status · verified status · blood group.

### Views

Grid (photo cards) · List (dense table) · Profile (full page at `/directory/{ulid}`).

### Privacy

Every card and profile is serialized by `DirectoryMemberResource`, which consults `member_privacy` per field. A hidden field is **absent from the JSON payload** — not blanked, not CSS-hidden. A member with `show_profile = false` does not appear in results at all.

### Done when

Search and every filter work, all three views render, and a test proves that a member with `show_phone = false` produces a payload containing no phone number on every directory route.

---

## 4. Batch management

Each batch page carries: name (`SSC 1990` / `এসএসসি ১৯৯০`), year, description, cover image, coordinators, member count, photos, events, announcements and discussions.

`batches.members_count` is a counter cache maintained by `MemberObserver` — the batch index would otherwise issue one `COUNT(*)` per batch.

### Batch coordinators

Assigned by an admin through `batch_coordinators`. Their reach is enforced in policies, not permission strings — see [04-roles-permissions.md §5](04-roles-permissions.md). A coordinator can edit their batch description and cover, view their batch's members, moderate their batch's posts, and publish batch-scoped announcements and albums. Nothing outside their batch.

---

## 5. Event management

Reusable for every event type: reunion, seminar, sports, cultural, fundraising, meeting, volunteer, general.

### Lifecycle

```
draft → published → registration_open → registration_closed → completed
                                      └→ cancelled (from any state)
```

### The date rule

`date_status` is `tba` or `announced`. While `tba`, `starts_at` is null and every surface renders **"তারিখ শীঘ্রই ঘোষণা করা হবে"**. The countdown component does not render at all. See [17-golden-jubilee.md](17-golden-jubilee.md).

### Registration

Members register from the event page; non-members register as guests and become `crm_contacts`. Stored per registration: registrant snapshot (survives member deletion), ticket type, guest list, amount due, payment status, attendance, check-in time and a random `qr_token`.

Capacity enforcement is a transactional check — over-capacity registrations become `waitlisted`, not rejected.

Unique `(event_id, member_id)` prevents double registration.

### Tickets & QR

`bacon/bacon-qr-code` renders an SVG QR encoding the registration **ULID**, not the integer id. The pass is viewable in the browser and printable.

### Check-in

`/admin/events/{event}/checkin` opens a scanner screen for volunteers holding `events.checkin`.

- Scanning writes an `event_checkins` row with the operator id, timestamp and gate.
- **Duplicate check-ins are prevented by a `UNIQUE` constraint on `event_registration_id`**, not by an application-level `if`. A second scan returns "already checked in at HH:MM by {operator}" rather than a database error.
- Rate limited at 120/min — a gate queue is bursty.

---

## 6. CRM

### Entities

`crm_contacts` covers prospects, volunteers, donors, sponsors, guests, partners and organizations. When a contact turns out to be an alumnus, `member_id` links them — **the person is never duplicated**.

### Pipeline

`new → contacted → interested → registered → verified → active → inactive → archived`

Rendered as a board with drag-to-advance, and as a select on every card so it works without a mouse.

**Unlike membership verification, this is not a locked state machine.** A relationship is not a workflow: a donor who went quiet may be moved straight back to `contacted`, and a prospect who walks in already registered skips three stages. Constraining that would make the committee fight the tool.

What IS enforced: every stage change goes through `PipelineService` and writes a `crm_activities` row of type `system`, recording who moved someone and when — even when the move was a drag on a board. The ordinary edit form **strips** `pipeline_status` and `owner_id` so neither can change without being recorded.

A move to the stage something is already at writes nothing; a board that fired an update on every drop would fill the timeline with noise.

### Activity timeline

One polymorphic `crm_activities` table, subject = `Member` **or** `CrmContact`, type = `note` | `call` | `email` | `meeting` | `task` | `system`.

**The union is the point.** A person may exist as a member AND as a contact — entered as a prospect, later registering, the two records linked. Their history is then split across two subjects, and a timeline showing only one half would be worse than no timeline: it would look complete while hiding the call that preceded the registration.

`ActivityLogger::timelineFor()` resolves both subjects and orders the union, so the feed reads the same from either end.

`system` rows are written by the SERVICES that do the thing — `VerificationService` on a status change, `EventRegistrar` on a registration, `PipelineService` on a stage move or an owner handover. Not by callers remembering to. **A form can never post one**: `system` is absent from the accepted type list, because those rows are the platform vouching that something happened and anyone able to write one could fabricate a history.

That is what makes the specification's example flow render as one ordered query:

```
John Doe
 ├─ [system]  Registration submitted           12 Jan 2026
 ├─ [call]    Admin contacted                   14 Jan 2026
 ├─ [system]  Identity verified                 15 Jan 2026
 ├─ [system]  Golden Jubilee registration       02 Feb 2026
 ├─ [system]  Donation received — ৳5,000        02 Feb 2026
 └─ [system]  Assigned to Reception team        10 Feb 2026
```

### Tasks & follow-ups

`crm_tasks` — title, due date, priority, assignee, completion. A task may hang off a contact, off a member, or off nothing at all: "book the hall" belongs to no particular person.

`is_overdue` is computed **server-side** in the resource, so a device with a wrong clock cannot hide a task that is late. Overdue counts surface on the admin dashboard, linking straight to the filter that lists them.

`CrmTaskPolicy` lets the assignee complete, reschedule or annotate their own task without holding `crm.manage` — a volunteer given a follow-up should not need the permission that lets them rewrite the CRM in order to tick it off.

### Tags

`crm_tags` + polymorphic `crm_taggables`, applying to members and contacts alike.

Tags are shared vocabulary rather than personal bookmarks, so creating one needs `crm.manage`: a CRM where everybody invents their own labels stops being searchable within a month. Colours are validated as hex — free text there ends up in a style attribute.

### Ownership

`owner_id` answers "whose job is this", **not** "who may look". Reading is deliberately not owner-scoped: a volunteer coordinator needs to see that the membership secretary already called someone, and a contact only one person can see is a contact only one person follows up.

Taking a contact off a colleague's list needs `crm.assign`; the owner can always hand it on themselves. A new contact belongs to whoever entered it, because an unowned contact is nobody's job — and the dashboard counts the unowned ones for exactly that reason.

---

## 7. Payments

See [09-payments.md](09-payments.md) for the gateway abstraction.

One `payments` ledger with a polymorphic `payable`: membership fee, event registration, donation or sponsorship. Statuses: `pending` | `paid` | `failed` | `cancelled` | `refunded`.

`ManualGateway` is the only implementation today — it records offline payments (cash, bank transfer, mobile financial services handled outside the platform) with the recording admin captured in `recorded_by` and an audit row written. A receipt number is issued on transition to `paid`.

**No payment row is ever soft-deleted.** Corrections are made by refunding and re-recording, both audited.

---

## 8. Donations & sponsorship

### Donations

Public `/donate` form, optionally anonymous, optionally tied to a campaign or an event. Public donor wall shows only non-anonymous, `is_public` donations.

### Sponsorship

Packages: Title Sponsor · Platinum · Gold · Silver · Partner · Custom. Each with an amount, benefits (bilingual), slot limit and display order.

Sponsors carry a logo, website, agreement document, amount and status. `is_public` controls appearance on the Golden Jubilee sponsor wall, ordered by tier then display order.

**Agreement documents go to the private disk** and are served only through an authorized controller. See [08-security-privacy.md](08-security-privacy.md).

---

## 9. Volunteers

Teams seeded: Registration, Reception, Guest Management, Media, Photography, Logistics, Finance, Technical, Hospitality.

A volunteer record carries skills, availability and location. Assignments link a volunteer to a team and optionally to an event, with a responsibility and shift window.

Volunteer Coordinators manage volunteers and hold `events.checkin` — they can staff and run the gate without being able to edit the event.

---

## 10. Committees

Types: executive, organizing, event, finance, media, volunteer, batch. Each has a term window and a display order.

Committee members link to a `Member` when the person is on the platform, and fall back to free-text name + photo when they are not — historic committee members predate the platform and must still be displayable.

---

## 11. Community

### Posting

Members create posts in a category: General · Alumni Reunion · Batch Discussion · School Memories · Career · Business · Community Support · Volunteer Activities · Golden Jubilee.

Batch Discussion posts carry a `batch_id` and are visible to that batch.

### Interaction

Comments (threaded one level), reactions (one per member per item, enforced by a unique constraint), photo uploads, and `@member:{ulid}` mentions resolved at render time.

### Moderation

Members report content with a reason. Moderators work a report queue and can hide a post, remove it, disable its comments, delete a comment, or suspend the author. Every moderation action is audited.

---

## 12. Content management

| Type                    | Notes                                                                                                        |
| ----------------------- | ------------------------------------------------------------------------------------------------------------ |
| Pages                   | Privacy policy and terms are `is_system` and cannot be deleted                                               |
| News                    | Featured flag, categories, view counter, SEO fields                                                          |
| Announcements / Notices | One table, `kind` column. Audience: public, members, batch, role. Time-windowed with `starts_at` / `ends_at` |
| Gallery                 | Albums with images, optionally tied to an event or batch                                                     |
| Alumni stories          | Member-submitted, admin-reviewed (`pending` → `published`), featured flag                                    |
| School history          | Unlimited milestones: year, title, description, image, order. Seeded with **1976 — school journey begins**   |
| FAQs                    | Grouped: general, jubilee, membership, payment, event                                                        |

Rich text editing and a shared media library serve all of them.

---

## 13. Communication

### Channels

| Channel    | Implementation                  | Status                                            |
| ---------- | ------------------------------- | ------------------------------------------------- |
| `database` | Laravel notifications           | in-app notification centre                        |
| `mail`     | Laravel Mail, provider-agnostic | `log` driver in dev; SES/Postmark/Mailgun in prod |
| **`sms`**  | **BulkSMSBD** (`bulksmsbd.net`) | first concrete SMS driver                         |
| `whatsapp` | —                               | architected, not implemented                      |

### SMS — BulkSMSBD integration

Bangladesh SMS gateway. Plain HTTP API, **no composer package required** — implemented with Laravel's HTTP client behind the `SmsChannel` contract.

```
App\Services\Communication\
├── Contracts\SmsChannel          send(to, message): SmsResult
│                                 sendMany(array $messages): Collection<SmsResult>
│                                 balance(): float
├── SmsManager                    driver resolution from config/sms.php
└── Sms\
    ├── BulkSmsBdChannel          production driver
    ├── LogSmsChannel             dev/test driver — writes to the log, sends nothing
    ├── SmsResult                 value object: ok, code, message, providerId
    └── BulkSmsBdException
```

**Endpoints** (per the vendor's developer documentation):

| Purpose                                | URL                                      |
| -------------------------------------- | ---------------------------------------- |
| Single / comma-separated recipients    | `http://bulksmsbd.net/api/smsapi`        |
| Many (different message per recipient) | `http://bulksmsbd.net/api/smsapimany`    |
| Balance                                | `http://bulksmsbd.net/api/getBalanceApi` |

**Parameters:** `api_key`, `senderid` (must be pre-approved by the vendor), `number` (`88017XXXXXXXX`, comma-separated for several), `message` (URL-encoded), `type` (`text` | `unicode`).

**Response codes handled:**

| Code          | Meaning                                          | Handling                                                |
| ------------- | ------------------------------------------------ | ------------------------------------------------------- |
| `202`         | Submitted successfully                           | mark recipient `sent`                                   |
| `1002`        | Sender ID invalid or disabled                    | fail the campaign, alert admin — configuration error    |
| `1003`        | Missing required fields                          | fail fast, log as a bug                                 |
| `1005`        | Internal error                                   | retry with backoff                                      |
| `1006`        | Balance validity unavailable                     | fail the campaign, alert admin                          |
| `1007`        | Insufficient balance                             | **halt the campaign**, alert admin, do not burn retries |
| `1011`        | User ID not found                                | configuration error                                     |
| `1012`        | Bengali masking required                         | retry as `type=unicode`                                 |
| `1013`–`1021` | Gateway / pricing / account configuration errors | fail the campaign, surface the raw code to the admin    |

**Bangla SMS rules — important for cost and correctness:**

- Bangla text **must** be sent with `type=unicode`. Sending Bangla as `text` produces mojibake at the handset.
- A Unicode SMS segment is **70 characters**, against 160 for GSM-7 Latin. A Bangla message therefore costs roughly 2.3x more per character. `SmsManager` computes and displays the segment count and estimated cost **before** an admin sends a campaign.
- `TemplateRenderer` picks `body_bn` or `body` by the recipient's `users.locale`, and the channel selects `type` from the rendered content, not from a global setting.

**OTP messages — a vendor content rule, not a style choice:**

BulkSMSBD mandates the body format for one-time passwords:

```
Your {Brand/Company Name} OTP is XXXX
```

A message that does not match is rejected or blocked at the gateway, so the format is enforced in `Sms\OtpMessage` rather than left to an editable template.

Two consequences follow:

1. **The OTP body is English and stays English.** It is deliberately _not_ passed through the translator — a Bangla OTP breaks the required format. This is the single user-facing string in the platform that is not localised, and that is intentional, not an oversight. A test asserts it stays English even when the active locale is `bn`.
2. **Because it is Latin-only it is GSM-7**, billing at 160 characters per segment rather than Unicode's 70. A correctly formatted OTP is therefore also the cheapest message the platform sends.

`SMS_OTP_BRAND` must be Latin-script. A Bangla brand name would push the message to Unicode and break the format, so `OtpMessage` sanitises the brand and throws if nothing usable remains — loudly, at build time, rather than having the gateway reject every OTP in production.

**Number normalisation:**

Recipients are normalised to the vendor's format — `88` followed by the full local 11-digit number _including its trunk zero_: `8801712345678`. Bangladesh's calling code is +880, so `880`+`1712345678` and `88`+`01712345678` spell the same number; the vendor documents the latter.

Accepted inputs: `01712345678`, `8801712345678`, `+8801712345678`, `1712345678`, and spaced or hyphenated variants. Anything that is not `01[3-9]` followed by eight digits is **failed locally and never dispatched** — the platform does not pay to send to a number that cannot be valid.

**Operational rules:**

- Phone numbers are normalized to `88` + 11 digits before sending; malformed numbers are marked `failed` locally and never dispatched.
- Campaign sends are chunked and queued (`SendCampaignBatch`), never sent in a request cycle.
- The API key lives in `.env` only (`BULKSMSBD_API_KEY`) and is never logged, never exposed to the frontend, and never written into `campaign_recipients.error`.
- `LogSmsChannel` is the default in `local` and `testing`, so **no test or dev run can send a real SMS or spend real balance**.
- Remaining balance is shown on the campaign screen via `getBalanceApi`, cached for 5 minutes.
- The API is plain HTTP on the vendor's side; requests are made server-to-server only and carry no member PII beyond the recipient number and the message body.

See [12-environment.md](12-environment.md) for the environment variables.

### Campaigns

An admin composes from a `message_templates` entry or free text, picks an audience (all members, a batch, a status, a role, or a custom filter), previews the recipient count and estimated SMS cost, and schedules or sends.

`campaign_recipients` tracks per-recipient state: `queued` → `sent` → `delivered` / `failed` / `opened` / `bounced` / `unsubscribed`. Open tracking is recorded only when the mail provider supports it; the schema does not assume it.

### Notifications

In-app (`database`) for: registration received, approved, rejected, correction requested, event registration, payment received, event reminder, announcement published, new discussion activity, admin message. Each is a Laravel notification class, so adding `sms` or `whatsapp` to its `via()` is a one-line change.

---

## 14. Reports

Eleven reports, each with filters applied **before** export: member list, batch, registration, verification, event registration, attendance, payment, donation, sponsor, volunteer, engagement.

Formats: CSV (streamed, no memory ceiling), XLSX where practical, PDF where the output is a document rather than a dataset.

Large exports are queued (`BuildReportExport`) and delivered as a download link, so a 40,000-row member export does not time out a request.

Export is a separate permission (`reports.export`) from viewing, and every export writes an audit row recording who exported which dataset with which filters — an export is a bulk disclosure of personal data.

---

## 15. Dashboards

### Admin

Cards: total members · verified · pending · new this month · active · event registrations · attendance · donations · sponsorships · membership fees · volunteers · engagement.

Charts: members over time · by batch · by country · by district · by profession · event registration trend · donation trend.

All chart data is loaded with `Inertia::defer` behind animated skeletons, computed by `ChartDataService` with driver-aware date grouping so the same code works on SQLite and MySQL.

### Member

Profile completion · membership status and number · upcoming events · registered events · payment history · donations · batch · community activity · notifications · digital membership card.

---

## 16. Digital membership card

`/my/card` renders name, Bangla name, photo, membership number, batch, school, verification status and a QR code.

The QR encodes `https://{host}/verify/member/{ulid}`.

The public verification page returns **name, Bangla name, batch, membership number, photo and verification status — and nothing else**, regardless of the member's privacy settings, because this is the minimum needed to confirm the card is genuine. It is `noindex` and rate limited.

---

## 17. Global search

`/admin/search` searches members, batches, events, news, pages, announcements and CRM contacts.

`GlobalSearchService` runs one indexed query per entity type, caps each at a small number of results, and paginates the combined set. Results are **permission-filtered** — a Batch Coordinator's search never surfaces a member outside their batch.

`SearchDriver` is an interface; `DatabaseSearchDriver` is the only implementation. See [15-roadmap.md](15-roadmap.md) for the Scout path.

---

## 18. Settings

Grouped, cached, edited from `/admin/settings/{group}`:

| Group          | Contents                                                                                                                                                                                                                                      |
| -------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `organization` | association Bangla/English name, **established year (2015)**, association logo, favicon, cover image                                                                                                                                          |
| `school`       | school Bangla/English name, **established year (1976)**, **EIIN**, address, phone, email, website, education board, head teacher, সভাপতি, school logo — _office-holder names live here, not in `.env`, so they can change without a redeploy_ |
| `contact`      | association email, phone, address, map                                                                                                                                                                                                        |
| `social`       | Facebook, YouTube, LinkedIn, X, website                                                                                                                                                                                                       |
| `registration` | open/closed, require approval, duplicate warning threshold                                                                                                                                                                                    |
| `membership`   | number format, fee amounts, fee periods                                                                                                                                                                                                       |
| `event`        | default currency, registration defaults                                                                                                                                                                                                       |
| `jubilee`      | theme line, hero copy, organizer block, countdown toggle, date announcement                                                                                                                                                                   |
| `notification` | which events notify whom, on which channels                                                                                                                                                                                                   |
| `seo`          | default meta title, description, OG image                                                                                                                                                                                                     |
| `privacy`      | `public_directory` toggle, default privacy flags for new members                                                                                                                                                                              |
| `system`       | default language, timezone, footer content                                                                                                                                                                                                    |

`is_public` settings are shared to the frontend through Inertia. The rest never leave the server.

---

## 19. SEO

Applies to public pages only.

Per-entity `meta_title`, `meta_description`, `og_image_path` on events, news, pages and stories, with sensible fallbacks to the `seo` settings group.

Also: canonical URLs, Open Graph and Twitter card tags, `sitemap.xml` generated from published content, `robots.txt`, clean slugs everywhere, and JSON-LD structured data for `Organization`, `Event` and `Article`.

`noindex` on `/verify/member/*`, all member routes and all admin routes.
