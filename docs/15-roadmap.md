# 15 — Future Roadmap

The platform is built to be extended without rewriting its core. Nothing here is built now; everything here has a defined seam.

---

## 1. Ready to switch on — no schema change

| Feature                     | What exists                                                                       | What is left                                   |
| --------------------------- | --------------------------------------------------------------------------------- | ---------------------------------------------- |
| **Public alumni directory** | `settings.privacy.public_directory`; resources already handle an anonymous viewer | flip the setting; re-verify privacy tests      |
| **WhatsApp notifications**  | `SmsChannel` contract, notification classes with `via()`                          | a `WhatsAppChannel` + Business API credentials |
| **Phone verification**      | `users.phone`, `phone_verified_at`, working BulkSMSBD channel                     | an OTP flow and rate limiting                  |
| **Push notifications**      | database notification classes                                                     | add a `broadcast`/FCM channel                  |
| **S3 / object storage**     | `MediaService` is disk-agnostic                                                   | set `FILESYSTEM_DISK=s3`                       |
| **Redis**                   | cache, queue and session drivers are config                                       | set the drivers                                |

## 2. Needs a new driver class only

### Online payment gateways

`PaymentGateway` contract + `PaymentManager` already exist; `ManualGateway` proves the seam. See [09-payments.md §6](09-payments.md).

Realistic order for Bangladesh: **SSLCommerz or ShurjoPay** (aggregators covering bKash, Nagad, Rocket and cards in one integration) before direct bKash/Nagad merchant integrations. Onboarding is a business process — registered organization, settlement account, merchant agreement — not a code task.

### Email providers

`MAIL_MAILER` is config. Amazon SES for volume, Postmark for deliverability on transactional mail.

### Search

`SearchDriver` contract + `DatabaseSearchDriver` exist. The `search_blob` + indexed `LIKE` approach is fine to roughly 50,000 members; past that, latency on multi-filter directory queries becomes noticeable.

Migration: install Laravel Scout + Meilisearch, add `Searchable` to `Member`, `Event`, `News`, `Post`, implement `MeilisearchDriver`, switch the config. **Privacy filtering stays in the resource layer**, so indexing never becomes a leak — but the index itself must exclude private fields.

## 3. Mobile application

The most likely first real need, driven by the Golden Jubilee gate.

**Phase 1 — volunteer check-in scanner.** Two endpoints (`POST /auth/login`, `POST /events/{slug}/checkin`) on top of the existing `CheckinService`. The camera is on the phone; the gate has poor desktop access. This is why check-in logic lives in a service rather than a controller.

**Phase 2 — member app.** Directory, membership card, events, notifications, community. Requires the full `/api/v1` surface in [10-api.md](10-api.md) plus Sanctum.

Everything server-side is already in place: ULIDs, QR tokens, privacy-filtered resources, permission-scoped queries, database notifications.

## 4. Community & networking extensions

Each is a new module beside the existing ones, not a change to them.

| Feature                       | Builds on                                       | Notes                                                                                                                                                                                         |
| ----------------------------- | ----------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Alumni business directory** | `members.business_info`, industry, organization | a listing model + public pages; privacy flags already govern workplace visibility                                                                                                             |
| **Job board**                 | members + notifications + community moderation  | jobs, applications; batch and industry targeting                                                                                                                                              |
| **Mentorship**                | member skills, batches, CRM activities          | mentor/mentee matching; the activity timeline already records interactions                                                                                                                    |
| **Blood donor directory**     | `members.blood_group` (already indexed)         | **needs its own explicit consent flag** — `show_blood_group` must be opt-in and separate from other privacy flags, because this data is requested in emergencies and must not leak by default |
| **Batch group chat**          | posts, batch scoping                            | real-time requires broadcasting                                                                                                                                                               |

## 5. Fundraising & recognition

| Feature                    | Builds on                                                                                                                                  |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| **Fundraising campaigns**  | `donations.campaign` exists — needs goals, progress bars, campaign pages                                                                   |
| **Scholarship management** | new module: applications, review workflow, disbursement through the existing payments ledger                                               |
| **Recurring donations**    | requires a real gateway with subscription support                                                                                          |
| **Digital certificates**   | the QR + `/verify/` pattern from membership cards generalizes to certificates of appreciation, committee service and volunteer recognition |

## 6. Analytics

Current reporting covers the eleven reports and seven charts in [05-modules.md](05-modules.md). Beyond that:

- Engagement scoring per member (logins, event attendance, posts, donations) — an aggregate over existing data
- Batch health dashboards
- Retention and churn on membership renewals
- Geographic distribution mapping

None of these require schema changes — the source data is already recorded.

## 7. Third language

If a third language is ever needed, the paired `_bn` column pattern stops scaling. Migration path:

1. Add a `content_translations` table: `translatable_type`, `translatable_id`, `locale`, `field`, `value`.
2. Backfill from the existing paired columns.
3. Change the `Translatable` trait's resolution to read from the new table.
4. Drop the `_bn` columns once verified.

The trait is the seam — **no controller, resource or component changes.** This is the reason the trait exists rather than models reading `title_bn` directly.

## 8. Not recommended

Recorded so future maintainers do not spend effort on them:

| Idea                               | Why not                                                                                                                                                             |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Microservices                      | 61 tables with heavy cross-module relationships. Splitting would add network calls and distributed transactions to solve a problem this scale does not have.        |
| Separate React SPA with a REST API | Inertia already gives SPA behaviour with server-side routing and authorization. Splitting doubles the authorization surface — the classic way privacy leaks appear. |
| A headless CMS                     | The content model is bilingual and tightly coupled to members, batches and events. An external CMS cannot express those relationships.                              |
| GraphQL                            | One consumer, well-understood queries. Adds an authorization surface with no benefit.                                                                               |
| Multi-tenancy                      | One school, one association.                                                                                                                                        |
| Real-time everywhere               | The community is discussion-paced, not chat-paced. Polling is sufficient; broadcasting adds infrastructure for a need that has not appeared.                        |

## 9. Suggested sequence after the Golden Jubilee

1. **Immediately after the event** — archive the Jubilee, publish the event gallery, convert attendees into active members, review what broke on event day.
2. **Within 3 months** — online payment gateway (membership renewals are the recurring revenue), email provider at volume, public directory decision.
3. **Within 6 months** — mobile check-in app (before the next event), business directory, fundraising campaigns.
4. **Within 12 months** — job board, mentorship, scholarship management.
5. **Ongoing** — search migration when membership passes ~50,000; third language only if the association actually needs one.
