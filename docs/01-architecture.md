# 01 — Architecture

## 1. Style: modular monolith

One Laravel application. No microservices, no separate frontends, no service mesh.

Modules are logical, not physical — they are namespaces inside stock Laravel directories, not a custom `app/Domains/` tree.

**Why stock directories:**

- Laravel Wayfinder generates TypeScript from `app/Http/Controllers/**`. A non-standard tree breaks generation paths.
- `php artisan make:*` writes to stock locations. Fighting it means every generated file needs moving.
- Larastan's Laravel extension resolves models, relations and facades assuming stock structure.
- The starter kit's existing code (`app/Actions/Fortify`, `app/Http/Controllers/Settings`) already follows this shape. Two conventions in one repo is worse than one imperfect convention.

Cohesion is achieved with **subdirectories inside** each stock folder (`Services/Membership/`, `Http/Controllers/Admin/`, `Requests/Member/`).

## 2. Three surfaces

```
┌─────────────────────────────────────────────────────────────┐
│                     Single Laravel app                       │
├──────────────────┬──────────────────┬───────────────────────┤
│  A. PUBLIC       │  B. MEMBER       │  C. ADMIN CRM         │
│  no auth         │  auth + verified │  auth + permission    │
│  SEO indexed     │  approved-only   │  audit logged         │
│                  │  for directory   │                       │
│  pages/public/*  │  pages/member/*  │  pages/admin/*        │
│  PublicLayout    │  MemberLayout    │  AdminLayout          │
└──────────────────┴──────────────────┴───────────────────────┘
                            │
              ┌─────────────┴─────────────┐
              │   Services + Policies      │  ← all business logic
              └─────────────┬─────────────┘
                            │
              ┌─────────────┴─────────────┐
              │   Eloquent models + DB     │
              └───────────────────────────┘
```

Each surface has its own layout, its own controller namespace, its own Form Request namespace, and its own API Resource shape. The **same model** is serialized differently per surface — this is the privacy mechanism, not an accident. See [08-security-privacy.md](08-security-privacy.md).

## 3. Technology

| Layer              | Choice                                | Version                                                   |
| ------------------ | ------------------------------------- | --------------------------------------------------------- |
| Runtime            | PHP                                   | 8.3+ (8.5 in dev)                                         |
| Framework          | Laravel                               | 13.x                                                      |
| Auth               | Laravel Fortify                       | 1.37+ (2FA, passkeys, email verification, password reset) |
| Frontend transport | Inertia.js                            | 3.x                                                       |
| UI                 | React                                 | 19.x                                                      |
| Styling            | Tailwind CSS                          | 4.x                                                       |
| Components         | shadcn/ui primitives (Radix)          | already in repo                                           |
| Route typing       | Laravel Wayfinder                     | 0.1.x                                                     |
| Build              | Vite / vite-plus                      | 8.x                                                       |
| DB (dev)           | SQLite                                | —                                                         |
| DB (prod)          | MySQL                                 | 8.0+                                                      |
| Tests              | Pest                                  | 5.x                                                       |
| Static analysis    | Larastan                              | level 7                                                   |
| Formatting         | Pint (laravel preset) + vite-plus fmt | —                                                         |

### Approved third-party packages

Only four. Each is justified; nothing else is added without asking.

| Package                     | Why it is necessary                                                                                                                                                                                                               |
| --------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `spatie/laravel-permission` | 11 roles x 47 permissions with per-request caching, Gate integration and middleware. Hand-rolling this correctly (cache invalidation, wildcard resolution, scoping) is a week of work on a security-sensitive surface.            |
| `bacon/bacon-qr-code`       | Sections 27/28 require QR membership cards and event check-in passes. Pure PHP, SVG output, no GD/Imagick dependency. There is no framework alternative.                                                                          |
| `intervention/image`        | Section 40 requires thumbnails and variants for profile photos, gallery, banners and sponsor logos. Laravel ships no image processing.                                                                                            |
| `barryvdh/laravel-dompdf`   | Sections 15/29 require PDF receipts, invoices and reports. **Caveat:** Bengali conjunct shaping in dompdf is unreliable — see [16-troubleshooting.md](16-troubleshooting.md). Latin-script documents only until proven otherwise. |

Everything else — RBAC UI, audit logging, media library, settings, localization, SEO, sitemap, search, CSV export, activity timeline — is written in-repo. Rationale: each is under ~300 lines and a package would impose schema we do not control.

## 4. `app/` directory tree

```
app/
├── Actions/
│   ├── Fortify/                    CreateNewUser, ResetUserPassword  (existing)
│   ├── Membership/                 RegisterMember, ApproveMember, RejectMember,
│   │                               SuspendMember, AssignMembershipNumber,
│   │                               RecalculateProfileCompletion
│   ├── Events/                     RegisterForEvent, CheckInAttendee, IssueTicket
│   ├── Crm/                        CreateContact, LogActivity, AdvancePipeline
│   └── Payments/                   RecordOfflinePayment, RefundPayment
│
├── Concerns/                       PasswordValidationRules, ProfileValidationRules (existing)
│                                   + Auditable, HasMedia, HasSeo, Translatable, HasUlid
│
├── Enums/                          *new base folder*
│   MemberStatus, RelationType, Gender, BloodGroup, EventType, EventStatus,
│   EventDateStatus, PaymentStatus, PaymentGatewayName, PipelineStage, CrmContactType,
│   CrmActivityType, TaskStatus, TaskPriority, SponsorTier, VolunteerStatus,
│   CommitteeType, PostCategory, ContentStatus, AnnouncementKind, AnnouncementLevel,
│   AudienceScope, ReportReason, CampaignChannel, CampaignRecipientStatus, Locale
│
├── Events/                         MemberRegistered, MemberApproved, MemberRejected,
│                                   PaymentRecorded, EventPublished, AttendeeCheckedIn
│
├── Exports/                        *new base folder*
│   Concerns/Exportable, MemberExport, BatchExport, RegistrationExport,
│   AttendanceExport, PaymentExport, DonationExport, SponsorExport,
│   VolunteerExport, VerificationExport, EngagementExport
│
├── Http/
│   ├── Controllers/
│   │   ├── Public/                 HomeController, AboutController, JubileeController,
│   │   │                           EventController, BatchController, NewsController,
│   │   │                           AnnouncementController, GalleryController,
│   │   │                           CommitteeController, StoryController,
│   │   │                           AchievementController, DonationController,
│   │   │                           SponsorshipController, ContactController,
│   │   │                           RegistrationController, MemberVerifyController,
│   │   │                           PageController, SitemapController, LocaleController
│   │   ├── Member/                 DashboardController, ProfileController,
│   │   │                           MembershipCardController, DirectoryController,
│   │   │                           MyEventController, MyPaymentController,
│   │   │                           MyDonationController, MyBatchController,
│   │   │                           CommunityController, NotificationController
│   │   ├── Admin/                  DashboardController, MemberController,
│   │   │                           VerificationController, BatchController,
│   │   │                           Crm/{ContactController, ActivityController,
│   │   │                             TaskController, PipelineController},
│   │   │                           EventController, RegistrationController,
│   │   │                           CheckinController, JubileeController,
│   │   │                           PaymentController, DonationController,
│   │   │                           SponsorController, VolunteerController,
│   │   │                           CommitteeController, ModerationController,
│   │   │                           Cms/{NewsController, AnnouncementController,
│   │   │                             GalleryController, PageController,
│   │   │                             StoryController, HistoryController, FaqController},
│   │   │                           ReportController, SettingController,
│   │   │                           UserController, RoleController,
│   │   │                           AuditLogController, SearchController
│   │   ├── Api/V1/                 (Phase 8 — architecture only)
│   │   └── Settings/               ProfileController, SecurityController  (existing)
│   │
│   ├── Middleware/                 HandleAppearance, HandleInertiaRequests (existing)
│   │                               + SetLocale, EnsureMemberApproved, LogAdminAction
│   │
│   ├── Requests/{Public,Member,Admin}/…
│   │
│   └── Resources/                  *new base folder*
│       PublicMemberResource, DirectoryMemberResource, AdminMemberResource,
│       EventResource, PublicEventResource, BatchResource, CrmContactResource,
│       PaymentResource, PostResource, NewsResource, …
│
├── Jobs/                           SendCampaignBatch, GenerateImageVariants,
│                                   BuildReportExport, RecalculateBatchCounts,
│                                   RebuildMemberSearchBlob, WriteAuditLog
│
├── Listeners/                      NotifyMemberOfApproval, NotifyAdminsOfRegistration,
│                                   IssueTicketOnRegistration, LogPaymentActivity
│
├── Models/                         (see 02-database-schema.md)
│
├── Notifications/                  MemberRegistered, MemberApproved, MemberRejected,
│                                   CorrectionRequested, EventRegistered, EventReminder,
│                                   PaymentReceived, AnnouncementPublished, AdminMessage
│
├── Observers/                      MemberObserver, EventObserver, PaymentObserver,
│                                   PostObserver, AuditObserver
│
├── Policies/                       one per model with non-trivial access
│
├── Providers/                      AppServiceProvider, FortifyServiceProvider (existing)
│                                   + AuthServiceProvider, EventServiceProvider
│
├── Services/                       *new base folder*
│   ├── Membership/                 MembershipService, VerificationService,
│   │                               MembershipNumberGenerator, ProfileCompletionCalculator
│   ├── Crm/                        ContactService, ActivityTimelineService, PipelineService
│   ├── Events/                     EventService, RegistrationService, TicketService,
│   │                               CheckinService
│   ├── Payments/                   PaymentManager, PaymentRecorder,
│   │                               Contracts/PaymentGateway, Gateways/ManualGateway
│   ├── Media/                      MediaService, ImageVariantGenerator
│   ├── Communication/              CampaignService, TemplateRenderer, SmsManager,
│   │                               Contracts/{MailChannel, SmsChannel},
│   │                               Sms/{BulkSmsBdChannel, LogSmsChannel},
│   │                               Sms/SmsResult, Sms/BulkSmsBdException
│   ├── Reporting/                  ReportBuilder, ChartDataService, DashboardMetrics
│   ├── Search/                     GlobalSearchService, Contracts/SearchDriver,
│   │                               Drivers/DatabaseSearchDriver
│   └── Settings/                   SettingsService  (cached key/value store)
│
└── Support/                        *new base folder*
    Locale, BanglaNumber, Qr, SlugFactory, Seo
```

### New base folders under `app/`

`Enums`, `Exports`, `Http/Resources`, `Services`, `Support` did not exist in the starter kit. `CLAUDE.md` requires approval for new base folders; the approved implementation plan grants it. All are conventional Laravel locations.

## 5. `resources/js/` directory tree

```
resources/js/
├── app.tsx                         (existing)
├── actions/  routes/  wayfinder/   generated by Wayfinder — never edited by hand
│
├── components/
│   ├── ui/                         28 shadcn primitives (existing) — REUSE, do not rewrite
│   ├── shared/                     locale-switcher, empty-state, error-state,
│   │                               loading-state, confirm-dialog, data-table,
│   │                               filter-bar, pagination, search-input,
│   │                               media-picker, rich-editor, drawer, tabs-nav,
│   │                               status-badge, date-display, money
│   ├── public/                     site-header, site-footer, hero, jubilee-banner,
│   │                               jubilee-countdown, milestone-timeline, stat-strip,
│   │                               event-card, news-card, announcement-strip,
│   │                               gallery-grid, committee-card, story-card,
│   │                               sponsor-wall, cta-band, batch-card
│   ├── member/                     profile-completion, membership-card, event-ticket,
│   │                               directory-card, directory-filters, post-composer,
│   │                               post-card, comment-thread, reaction-bar
│   └── admin/                      stat-card, chart-line, chart-bar, chart-donut,
│                                   crm-timeline, pipeline-board, verification-panel,
│                                   checkin-scanner, audit-row, permission-matrix
│
├── layouts/
│   ├── public-layout.tsx           header + footer + announcement strip + locale switcher
│   ├── member-layout.tsx           member nav + notifications
│   ├── admin-layout.tsx            wraps existing app-sidebar-layout.tsx
│   ├── app-layout.tsx              (existing)
│   ├── auth-layout.tsx             (existing)
│   └── settings/layout.tsx         (existing)
│
├── pages/
│   ├── public/                     home, about, about-school, events, event-show,
│   │                               batches, batch-show, news, news-show, announcements,
│   │                               gallery, album-show, committees, stories, story-show,
│   │                               achievements, donate, sponsorship, contact, join,
│   │                               verify-member, page-show
│   ├── jubilee/                    index, schedule, sponsors, faq, register
│   ├── member/                     dashboard, profile, card, directory, directory-show,
│   │                               events, payments, donations, batch, community,
│   │                               community-show, notifications
│   ├── admin/                      dashboard, members/*, crm/*, batches/*, events/*,
│   │                               jubilee/*, payments/*, donations/*, sponsors/*,
│   │                               volunteers/*, committees/*, community/*, cms/*,
│   │                               reports/*, settings/*, users/*, roles/*, audit-logs
│   ├── auth/                       (existing — 7 pages)
│   ├── settings/                   (existing — 3 pages)
│   └── dashboard.tsx               (existing — becomes a role-aware redirect shim)
│
├── hooks/                          (existing 8) + use-translation, use-permission,
│                                   use-bn-number, use-debounced-search
├── lib/                            utils.ts (existing) + format.ts, permissions.ts, seo.ts
└── types/                          (existing 5) + member.ts, event.ts, crm.ts, cms.ts
```

### Reused existing assets

Nothing below is rewritten:

| Asset                                                        | Reused for          |
| ------------------------------------------------------------ | ------------------- |
| `components/ui/*` (28 primitives)                            | every surface       |
| `components/app-sidebar.tsx`, `nav-main.tsx`, `nav-user.tsx` | admin sidebar       |
| `layouts/app-sidebar-layout.tsx`                             | admin layout base   |
| `layouts/auth-layout.tsx` + `pages/auth/*`                   | all authentication  |
| `hooks/use-flash-toast.ts`                                   | all flash messaging |
| `hooks/use-appearance.tsx`                                   | dark mode           |
| `hooks/use-mobile.tsx`, `use-mobile-navigation.ts`           | responsive nav      |
| `components/input-error.tsx`                                 | all forms           |
| `components/ui/sonner.tsx`                                   | all toasts          |
| `components/breadcrumbs.tsx`                                 | admin + member      |

## 6. Request lifecycle

```
HTTP request
   │
   ├─ web middleware
   │    ├─ EncryptCookies (except appearance, sidebar_state)
   │    ├─ HandleAppearance          (existing)
   │    ├─ SetLocale                 ← new: url → session → user.locale → config
   │    ├─ HandleInertiaRequests     (existing, extended with locale + translations
   │    │                             + auth.permissions + settings.public + flash)
   │    └─ AddLinkHeadersForPreloadedAssets
   │
   ├─ route middleware (auth, verified, member.approved, can:*, throttle:*)
   │
   ├─ Controller (thin)
   │    ├─ Form Request  → validation + authorize()
   │    ├─ Policy        → Gate check
   │    ├─ Service/Action → ALL business logic
   │    └─ API Resource  → surface-specific serialization (privacy applied HERE)
   │
   ├─ Inertia::render('page', [...props])
   │    └─ Inertia::defer(...) for charts and heavy lists
   │
   └─ React page component (presentation only — no business logic)
```

### The rule about business logic

- **Controllers** validate, authorize, delegate, respond. Typically 5–15 lines per action.
- **Services** own multi-step operations and cross-model coordination.
- **Actions** own single, nameable business operations (`ApproveMember`). Invokable classes.
- **Models** own relations, casts, scopes, accessors. No service calls from models.
- **Observers** own side effects that must never be forgotten (audit rows, counter caches, search blob rebuilds).
- **React components** own layout and interaction. They compute nothing the server could have computed.

## 7. Cross-cutting patterns

### Enums as string columns

Never `ENUM` in SQL. Every status/type column is `string` with a PHP backed enum cast. This is the single largest SQLite↔MySQL portability trap: altering an enum behaves differently on each driver, and SQLite has no native enum at all.

```php
$table->string('status', 32)->default(MemberStatus::Pending->value)->index();

// Model:
protected function casts(): array
{
    return ['status' => MemberStatus::class];
}
```

### ULIDs for public identifiers

Integer primary keys internally (smaller indexes, faster joins). A separate indexed `ulid` column is what appears in URLs and QR payloads, so `/directory/01JBX…` cannot be walked by incrementing a number.

Applies to: `members`, `events`, `event_registrations`, `payments`, `crm_contacts`.

### Counter caches

`batches.members_count`, `posts.comments_count`, `posts.reactions_count` are maintained by observers. Reason: the batch index page would otherwise issue one `COUNT(*)` per batch.

### Denormalized search

`members.search_blob` holds a lowercase concatenation of name, Bangla name, organization, occupation and city, rebuilt by an observer. Directory search becomes one indexed `LIKE` instead of five OR-ed column scans. Ceiling is roughly 50k rows; see [15-roadmap.md](15-roadmap.md) for the Scout migration path.

### Polymorphic timelines

`crm_activities`, `comments`, `reactions`, `content_reports`, `media` and `payments.payable` are polymorphic. This is what prevents the duplicate-concept problem the specification warns about — a member does not need a shadow contact row in order to have an activity history.

### Queued work

Anything that can take more than ~200 ms off the request path: campaign sends, image variant generation, report export building, batch counter recalculation, search blob rebuilds at bulk-import scale.

### Audit trail

The `Auditable` trait plus `AuditObserver` write `audit_logs` rows on `created`/`updated`/`deleted` for models that opt in, capturing changed attributes only (not the whole row), plus actor, IP and user agent. Admin-only mutations that are not model writes (a bulk export, a role change) log explicitly through the `LogAdminAction` middleware.

## 8. Portability: SQLite (dev) → MySQL (prod)

Rules enforced throughout:

1. No SQL `ENUM` — string column plus PHP enum cast.
2. No SQLite-specific functions (`strftime`, `json_extract` in a `WHERE`).
3. No MySQL-specific functions (`DATE_FORMAT`, `GROUP_CONCAT`) in application queries — date grouping goes through a driver-aware helper in `ChartDataService`.
4. Explicit, short index names. MySQL caps identifiers at 64 characters and auto-generated names on long table+column combinations overflow.
5. `string` columns always given an explicit length, because of MySQL index key length limits under `utf8mb4`.
6. Foreign keys always declared. SQLite needs `PRAGMA foreign_keys=ON`, which Laravel sets.
7. No raw `INSERT OR IGNORE` / `ON DUPLICATE KEY` — use `updateOrCreate` / `upsert`.
8. CI runs the full suite against **both** drivers.

The migration path from an existing SQLite dataset is in [13-deployment.md](13-deployment.md).

## 9. Extension points

Each of these is an interface with one implementation today, so adding a second requires no changes to callers:

| Interface                                      | Today                                                            | Later                                |
| ---------------------------------------------- | ---------------------------------------------------------------- | ------------------------------------ |
| `Services\Payments\Contracts\PaymentGateway`   | `ManualGateway`                                                  | bKash, Nagad, SSLCommerz, Stripe     |
| `Services\Search\Contracts\SearchDriver`       | `DatabaseSearchDriver`                                           | Scout + Meilisearch                  |
| `Services\Communication\Contracts\MailChannel` | Laravel Mail                                                     | SES / Postmark / Mailgun             |
| `Services\Communication\Contracts\SmsChannel`  | **`BulkSmsBdChannel`** (bulksmsbd.net) + `LogSmsChannel` for dev | other BD gateways, WhatsApp Business |
| Laravel notification channels                  | `database`, `mail`, **`sms`**                                    | `whatsapp`, `broadcast`, push        |
| `Services\Media\MediaService` disks            | `public`, `local`                                                | S3 / object storage                  |

See [15-roadmap.md](15-roadmap.md).
