# Changelog

Phase-by-phase record of the SSHS Alumni Platform build. Newest first.

Format loosely follows [Keep a Changelog](https://keepachangelog.com/). Dates are ISO (YYYY-MM-DD).

---

## Phase 0 — Foundation · 2026-09-17

**Status: complete**

### Added

- **Packages** — the four approved: `spatie/laravel-permission`, `bacon/bacon-qr-code`, `intervention/image`, `barryvdh/laravel-dompdf`.
- **Schema** — 11 grouped migrations, 61 tables, portability rules applied throughout.
- **Models** — 46 enums with locale-resolved labels, 45 models with typed relations, explicit fillable lists and `@property` docblocks generated from the real schema, 45 factories.
- **Concerns** — `HasUlid`, `Translatable`, `Auditable`.
- **Services** — settings (cached, grouped), media (re-encoding, variants, SVG sanitisation), SMS (contract + BulkSMSBD + log driver + manager), profile-completion scoring.
- **Observers** — `MemberObserver` (search blob, completion score, batch counter, privacy row), `AuditObserver` (changed attributes only, secrets stripped).
- **Localization** — `SetLocale` middleware, locale switch route, translation sharing, `BanglaNumber`, seven language-file pairs with key parity enforced by a test.
- **Frontend** — brand tokens from the real logos, self-hosted Noto Sans Bengali, `useTranslation`, `usePermission`, format helpers, locale switcher.
- **Seeders** — RBAC, settings, batches, school history, Jubilee, reference data, plus production and demo entry points.
- **Commands** — `make:admin`, `demo:purge`, `sms:balance`, `sms:test`.

### Bugs found and fixed during the phase

| Bug                                              | Why it mattered                                                                                                                                                                                                   |
| ------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Font `subsets` defaulted to `['latin']`          | Noto Sans Bengali downloaded with a unicode-range of U+0000–00FF only. The font looked installed but the browser would never apply it to a Bengali character — Bangla would fall back to a system font, or boxes. |
| `SettingsSeeder` read `env()` directly           | `env()` returns null once `config:cache` has run, which production always does. Seeding a cached install would have written empty organization and school values.                                                 |
| `User` had a null locale in memory               | Column defaults apply on insert, not in Eloquent — so the instance `actingAs()` and post-registration redirects use had no locale, and `SetLocale` crashed on it.                                                 |
| SMS number normalisation dropped the trunk zero  | Produced `881712345678` instead of the `8801712345678` the vendor documents.                                                                                                                                      |
| A Bangla OTP brand sanitised to `"-"`, not empty | Passed the emptiness check and would have produced a malformed OTP.                                                                                                                                               |
| BulkSMSBD code `1032` undocumented               | IP not whitelisted. The send endpoint enforces it, the balance endpoint does not — so a green balance check proves nothing about sending.                                                                         |
| Intervention Image 4.3 API drift                 | `read()` → `decodePath()`, `encodeByExtension()` → `encodeUsingFileExtension()`.                                                                                                                                  |

### Verified, not assumed

- Every model boots; all 143 relations resolve against the real schema; every fillable and cast column exists.
- All 45 factories create a valid row.
- A real seed run produced 47 permissions, 11 roles, 46 batches, 2 milestones, 9 volunteer teams, 6 sponsorship tiers — with the Jubilee holding no date and `dateIsTba()` true.
- Three real SMS delivered through the live gateway (English 1 segment, Bangla 2 segments, OTP 1 segment), confirming the segment-cost model empirically.
- Bangla renders correctly in the browser with conjuncts (ক্ষ, ন্ত, স্ত, র্ণ) forming properly and the specified 1.8 line-height applied.
- **157 tests, 347 assertions. PHPStan level 7, TypeScript and the frontend linter all clean.**

### Corrected

The permission count in the docs was 60; the actual catalogue is 47. The matrix in `docs/04` always listed 47 — the summary figure was an arithmetic error, so the documentation was wrong rather than the code incomplete.

### Not done in this phase

Public, member and admin layouts move to Phase 1: a layout with no pages to render cannot be verified, so building it here would mean committing code nothing exercises.

### Still unverified

The migrations have not been run against MySQL — no server is available in this environment. The portability rules are applied by hand; the CI matrix is what will prove them.

---

## Phase D.1 — Brand assets & verified organizational facts · 2026-09-17

**Status: complete**

The association supplied both official logos, and the school's official site was located. Three contradictions with the original brief were found and resolved before any code was written.

### Corrected

|                      | Brief said                          | Verified                                       | Source                                          |
| -------------------- | ----------------------------------- | ---------------------------------------------- | ----------------------------------------------- |
| School English name  | Govt. Sabuj Shikshyatan High School | **Sabuj Shikshayatan Government High School**  | [sabujsghs.edu.bd](https://sabujsghs.edu.bd)    |
| Association founding | implied 1976                        | **2015**                                       | ribbon on the association logo (স্থাপিত : ২০১৫) |
| Palette              | deep green + gold + maroon          | **green + purple + red**; gold on neither mark | the supplied logos                              |

### Added — verified school data

EIIN **105070** · established **1976** · Hafiz Jute Mills Ltd, Baro Aulia, Sitakunda, Chattogram · 01745950025 · sabujshikha@yahoo.com · Head Teacher Nurjahan Akter · সভাপতি মো: জসিম উদ্দিন · Chattogram education board · https://sabujsghs.edu.bd

### Decisions

| Decision              | Outcome                                                                                                                                                                                        |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| School name           | Keep **সরকারি / Government** — the school was nationalised after the logos were made. Logo artwork is **not** altered; site text uses the current legal name from `settings.school`            |
| "Fifty years" framing | **The school's** fifty years (1976–2026). The association (est. 2015) is credited as **organiser**. Both founding dates seeded as school-history milestones                                    |
| Palette               | Green primary + association purple accent + red alerts, all sampled from the marks. **Gold restricted to the সুবর্ণজয়ন্তী ceremonial pages**, where "golden jubilee" gives it meaning         |
| Settings groups       | `organization` and `school` **split**, so the two bodies' names and founding years can never be conflated. Office-holder names live in settings, not `.env`, so they change without a redeploy |

### Changed

`README.md` · `docs/00-overview.md` (new §3 Verified organizational facts; sections renumbered) · `docs/02-database-schema.md` (settings groups, milestone seed) · `docs/05-modules.md` (settings table) · `docs/07-branding-ui.md` (§1 identity and logos, §2 palette rewritten from the real assets) · `docs/12-environment.md` (split org/school env blocks) · `docs/17-golden-jubilee.md` (new §0 Whose fifty years, hero composition, milestone seed)

### Outstanding

- [ ] **Logo files not yet on disk.** Save the two supplied images to `public/brand/logo-association.png` and `public/brand/logo-school.png`. Hex values in `docs/07` are read from the images and accurate to within a shade; they will be re-sampled exactly once the files land.
- [ ] Favicon, cover, OG and Jubilee banner to be derived from the association mark.
- [ ] School's own history page is empty (_বিস্তারিত আসছে..._), so the founding narrative must come from the committee.

---

## Phase D — Documentation · 2026-09-17

**Status: complete**

Written before any application code, so the design could be reviewed and corrected before a single table existed.

### Added

- `README.md` — project entry point, quick start, principles, doc index, production checklist
- `CHANGELOG.md` — this file
- `docs/00-overview.md` — product scope, Golden Jubilee context, bn/en glossary, scale targets
- `docs/01-architecture.md` — modular monolith, three surfaces, `app/` and `resources/js/` trees, request lifecycle, cross-cutting patterns, SQLite→MySQL portability rules, extension points
- `docs/02-database-schema.md` — 61 tables with every column, type and index; ERD; **documented rationale for each of the six deviations from the original specification's table list**
- `docs/03-routes.md` — every public / member / admin route with name, controller, middleware and rate limit; planned API surface
- `docs/04-roles-permissions.md` — 60 permissions, 11 roles, full matrix, scoped-policy rules, seeding, required tests
- `docs/05-modules.md` — functional spec for all 19 modules
- `docs/06-localization.md` — বাংলা/English architecture, lang file layout, `Translatable` trait, Noto Sans Bengali, Bangla numerals, translator workflow
- `docs/07-branding-ui.md` — color tokens with contrast rules, typography, three visual registers, component inventory, responsive and accessibility requirements
- `docs/08-security-privacy.md` — threat model, privacy field matrix, resource-layer enforcement, upload rules, audit logging, 16 mandatory security tests
- `docs/09-payments.md` — `PaymentGateway` contract, offline recording flow, receipts, gateway-addition checklist
- `docs/10-api.md` — versioned API plan, Sanctum rationale, mobile readiness
- `docs/11-installation.md` — prerequisites, setup, seeders, demo credentials, first-run problems
- `docs/12-environment.md` — every environment variable, dev vs production, secret handling
- `docs/13-deployment.md` — Linux/Nginx/MySQL, queue worker, scheduler, backup, SQLite→MySQL migration, go-live checklist
- `docs/14-testing.md` — strategy, mandatory security tests, per-phase coverage, CI matrix
- `docs/15-roadmap.md` — post-2026 extensibility, and explicitly what _not_ to build
- `docs/16-troubleshooting.md` — Bangla rendering, dompdf limitation, queues, BulkSMSBD error codes, deploy issues
- `docs/17-golden-jubilee.md` — microsite spec, the TBA date rule, event-day operations

### Decisions recorded

| Decision              | Outcome                                                                                                                                                                                                                       |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Application structure | Modular monolith in **stock Laravel directories**, module-namespaced inside — not a custom `app/Domains/` tree, which would break Wayfinder, `artisan make:*` and Larastan                                                    |
| Production database   | **MySQL 8**; SQLite stays the development default. SQLite serializes writes and would fail at the Golden Jubilee check-in gate                                                                                                |
| Alumni directory      | **Members-only**, public page shows aggregate counts. Admin-togglable via `settings.privacy.public_directory` without a code change                                                                                           |
| Third-party packages  | Four approved: `spatie/laravel-permission`, `bacon/bacon-qr-code`, `intervention/image`, `barryvdh/laravel-dompdf`. Everything else written in-repo                                                                           |
| SMS gateway           | **BulkSMSBD** (`bulksmsbd.net`) as the first concrete `SmsChannel` driver — plain HTTP, no package. `log` driver is the default outside production so no test run can spend real balance                                      |
| Golden Jubilee        | Modelled as a flagship `events` row, **not** a separate subsystem. No date literal anywhere in the codebase                                                                                                                   |
| Schema consolidations | Six documented merges (`member_profiles`, `crm_notes`, `event_attendance`, `invoices`/`receipts`, `notices`, `batch_members`) following the specification's own instruction to avoid redundant tables and duplicated concepts |
| Privacy enforcement   | API Resource layer, server-side. A private field is **absent from the payload**, never blanked or CSS-hidden                                                                                                                  |

### Known risks carried forward

| Risk                                                 | Mitigation                                                                                                                                            |
| ---------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| SQLite dev / MySQL prod drift                        | portable migration rules in `docs/01` §8; CI runs the suite on both drivers                                                                           |
| **Bengali conjunct shaping in dompdf is unreliable** | tested early in the phase that builds receipts; fallback is Latin-script financial documents plus browser print-to-PDF for bilingual member documents |
| Official logo not yet supplied                       | labelled placeholder at `public/brand/`; swapping it is a file replace                                                                                |
| Search ceiling ~50k members                          | `search_blob` + indexed `LIKE` now; Scout + Meilisearch path documented                                                                               |
| Email deliverability at volume                       | requires a real provider plus SPF/DKIM/DMARC — a DNS task, documented not coded                                                                       |
| Queue worker and scheduler required                  | campaigns and exports fail **silently** without them; covered in deployment docs and the go-live checklist                                            |
| Bangla SMS costs ~2.3x Latin                         | Unicode segments are 70 chars vs 160; campaign screen shows segment count and estimated cost before sending                                           |

---

## Upcoming

| Phase                                     | Scope                                                                                                                                                 |
| ----------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| **1 — Auth, roles, registration**         | role admin UI · public, member and admin layouts · admin shell · five-step public registration                                                        |
| **2 — Profiles, verification, directory** | member dashboard & profile · verification workflow · batches & coordinators · members-only directory                                                  |
| **3 — Events, Jubilee, QR**               | event CRUD & lifecycle · registration & tickets · QR passes · check-in with duplicate prevention · Jubilee microsite · membership card                |
| **4 — CRM**                               | contacts · pipeline · activity timeline · tasks · tags                                                                                                |
| **5 — Money, volunteers, committees**     | payment abstraction · fees · donations · sponsors · receipts · volunteers · committees                                                                |
| **6 — Community**                         | posts · comments · reactions · reports · moderation                                                                                                   |
| **7 — CMS**                               | pages · news · announcements · gallery · stories · school history · FAQs · media library                                                              |
| **8 — Reports & hardening**               | 11 reports · charts · global search · campaigns (mail + SMS) · notifications · audit viewer · SEO · performance · security sweep · doc reconciliation |
