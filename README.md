<div align="center">

# প্রাক্তন ছাত্র-ছাত্রী পরিষদ
### সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়

**Former Students Association** *(est. 2015)*
**Sabuj Shikshayatan Government High School** *(est. 1976 · EIIN 105070 · Sitakunda, Chattogram)*

সুবর্ণজয়ন্তী ২০২৬ · Golden Jubilee 2026 · ১৯৭৬ — ২০২৬

*৫০ বছরের গৌরবময় পথচলা*

</div>

---

A permanent bilingual (বাংলা / English) digital platform for the alumni association: a public community website, a member area, and an administrative CRM.

Its first major use is the **Golden Jubilee 2026**, but it is built to serve the association for decades afterwards. The Jubilee is modelled as the first *flagship event* inside a general-purpose event system — not as a special subsystem that becomes dead code in 2027.

> **Two organizations, two founding dates.** The **school** was established in **1976** — the fifty years being celebrated are its. The **association** was established in **2015** and is the body organising the celebration. The platform keeps these separate everywhere. See [docs/00-overview.md §3](docs/00-overview.md).

> **The Golden Jubilee date is not yet fixed.** Until the committee publishes it from the admin panel, every public surface shows **"তারিখ শীঘ্রই ঘোষণা করা হবে"**. No date is hard-coded anywhere in the codebase. See [docs/17-golden-jubilee.md](docs/17-golden-jubilee.md).

---

## What it does

| | |
|---|---|
| **Membership** | multi-step registration · verification workflow · membership numbers · digital QR membership card |
| **Directory** | searchable alumni directory with per-field privacy controls (members-only) |
| **Batches** | batch pages by SSC year · coordinators · batch discussions |
| **Events** | reusable event system · tickets · registration · QR check-in · Golden Jubilee microsite |
| **CRM** | contacts · pipeline · polymorphic activity timeline · tasks · tags |
| **Money** | membership fees · donations · sponsorships · offline payment recording · receipts |
| **People** | volunteers & teams · committees |
| **Community** | posts · comments · reactions · moderation |
| **Content** | news · announcements · gallery · alumni stories · school history · pages · FAQs |
| **Communication** | email campaigns · **SMS via BulkSMSBD** · in-app notifications |
| **Operations** | 11 roles / 60 permissions · audit logs · 11 reports with export · global search |

---

## Stack

Laravel 13 · PHP 8.3+ · Inertia.js 3 · React 19 · Tailwind CSS 4 · Laravel Fortify (2FA + passkeys) · Wayfinder · Pest 5 · Larastan 7

**SQLite** in development · **MySQL 8** in production. Migrations are portable and CI runs the suite on both.

Four third-party packages, each justified in [docs/01-architecture.md §3](docs/01-architecture.md): `spatie/laravel-permission`, `bacon/bacon-qr-code`, `intervention/image`, `barryvdh/laravel-dompdf`. Everything else is written in-repo.

---

## Quick start

```bash
composer setup
php artisan migrate:fresh --seed
php artisan storage:link
composer dev
```

Then open **http://sshs-alumni.test/** (or `http://localhost:8000`).

Full instructions, prerequisites and required PHP extensions: **[docs/11-installation.md](docs/11-installation.md)**.

### Demo credentials — development only

| Email | Password | Role |
|---|---|---|
| `admin@example.test` | `ChangeMe123!` | Super Admin |
| `member@example.test` | `ChangeMe123!` | Member (approved) |
| `pending@example.test` | `ChangeMe123!` | Member (pending) |

> ### ⚠️ This password must be changed before production.
>
> `DemoSeeder` refuses to run when `APP_ENV=production`, so these accounts cannot be created on a production deploy. If a development database is ever promoted, run `php artisan demo:purge` first. Create the real administrator with `php artisan make:admin`.

---

## Branding assets

Both official marks have been supplied by the association. **No AI-generated logo is used or substituted.**

| Asset | Path |
|---|---|
| Association logo | `public/brand/logo-association.png` |
| School logo | `public/brand/logo-school.png` |
| Favicon | `public/brand/favicon.svg` + `.ico` |
| Cover / OG / Jubilee banner | `public/brand/cover.jpg` · `og.jpg` · `jubilee-banner.jpg` |

Palette is derived from the marks — **green primary, association purple accent, red for alerts**. Gold is restricted to the সুবর্ণজয়ন্তী pages, since it appears on neither logo.

> Both marks read সবুজ শিক্ষায়তন উচ্চ বিদ্যালয় without সরকারি because they predate the school's nationalisation. **The artwork is correct and is not altered**; the site's text uses the current legal name.

To update an asset: replace the file and run `npm run build`. No code changes. Logo, favicon and cover can also be uploaded from `/admin/settings/organization`, which takes precedence.

Specifications and usage rules: [docs/07-branding-ui.md §1–2](docs/07-branding-ui.md).

---

## Documentation

| Doc | Contents |
|---|---|
| [00 — Overview](docs/00-overview.md) | product scope, Golden Jubilee context, bn/en glossary |
| [01 — Architecture](docs/01-architecture.md) | modular monolith, directory trees, cross-cutting patterns |
| [02 — Database Schema](docs/02-database-schema.md) | 61 tables, ERD, indexes, deviations from the original spec |
| [03 — Routes](docs/03-routes.md) | every route, name, middleware, rate limit |
| [04 — Roles & Permissions](docs/04-roles-permissions.md) | 11 roles × 60 permissions, full matrix |
| [05 — Modules](docs/05-modules.md) | functional spec per module, incl. BulkSMSBD integration |
| [06 — Localization](docs/06-localization.md) | বাংলা/English architecture, fonts, numerals |
| [07 — Branding & UI](docs/07-branding-ui.md) | design system, components, accessibility |
| [08 — Security & Privacy](docs/08-security-privacy.md) | threat model, privacy enforcement, required tests |
| [09 — Payments](docs/09-payments.md) | gateway abstraction, offline recording, receipts |
| [10 — API](docs/10-api.md) | versioned API plan, mobile readiness |
| [11 — Installation](docs/11-installation.md) | local setup |
| [12 — Environment](docs/12-environment.md) | every env var, dev vs prod |
| [13 — Deployment](docs/13-deployment.md) | Linux, Nginx, MySQL, queue, scheduler, backup |
| [14 — Testing](docs/14-testing.md) | strategy, mandatory security tests, CI |
| [15 — Roadmap](docs/15-roadmap.md) | post-2026 extensibility |
| [16 — Troubleshooting](docs/16-troubleshooting.md) | Bangla rendering, queues, SMS codes, deploys |
| [17 — Golden Jubilee](docs/17-golden-jubilee.md) | microsite, the TBA date rule, event-day operations |

---

## Development

```bash
composer dev                  # server + queue + logs + vite
php artisan test --compact    # test suite
composer ci:check             # lint + Larastan 7 + tests (what CI runs)
vendor/bin/pint --dirty       # format changed PHP
npm run check                 # frontend lint
```

Project conventions live in `CLAUDE.md` / `AGENTS.md`, and domain skills in `.claude/skills/`.

---

## Core principles

1. **Privacy before features.** A private field is *absent from the response payload* — not blanked, not hidden with CSS. Enforced once, in the API Resource layer, so it cannot drift between the web app and a future API.
2. **Server-side authorization, always.** The frontend receives a permission list purely to hide buttons. Policies and middleware decide.
3. **Bilingual from the first line.** No user-facing string is hard-coded in a component.
4. **Auditability.** Every administrative action touching a person, money or published content leaves an audit row.
5. **One concept, one table.** Consolidations from the original specification are documented with reasons in [docs/02](docs/02-database-schema.md).
6. **Extensible, not over-engineered.** Payment gateways, SMS channels and search drivers sit behind interfaces. Everything else is plain Laravel.

---

## Production checklist

Before going live, work through [docs/13-deployment.md §12](docs/13-deployment.md). The items most often missed:

- `APP_DEBUG=false`
- `APP_URL` set to the final HTTPS domain **before** any QR code is generated
- MySQL with `utf8mb4` (not `utf8` — it cannot store Bengali correctly)
- Queue worker running under supervisor, **restarted on every deploy**
- Scheduler cron entry installed
- Every demo account deleted or re-passworded
- SPF / DKIM / DMARC published for the sending domain
- Backup script installed *and a restore drill completed*

---

## License

Proprietary — built for প্রাক্তন ছাত্র-ছাত্রী পরিষদ, সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়, সীতাকুণ্ড, চট্টগ্রাম.

School website: [sabujsghs.edu.bd](https://sabujsghs.edu.bd)
