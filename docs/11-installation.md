# 11 — Installation (Local Development)

---

## 1. Prerequisites

| Requirement | Version | Notes |
|---|---|---|
| PHP | **8.3+** | 8.5 used in development |
| Composer | 2.x | |
| Node.js | **20+** | 22 LTS recommended |
| npm | 10+ | |
| SQLite | 3.35+ | bundled with PHP on most systems |
| Git | any | |

### Required PHP extensions

`pdo_sqlite`, `pdo_mysql` (for production parity testing), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `bcmath`, **`gd`** (Intervention Image), `zip`, `curl`.

Verify:

```bash
php -m | grep -E "pdo_sqlite|pdo_mysql|mbstring|gd|bcmath|fileinfo|curl"
```

`gd` is the one people miss. Without it, every image upload fails.

---

## 2. Setup

```bash
git clone <repository-url> sshs-alumni
cd sshs-alumni
```

```bash
composer setup
```

`composer setup` (already defined in `composer.json`) runs: `composer install` → copy `.env.example` to `.env` if missing → `php artisan key:generate` → `php artisan migrate --force` → `npm install` → `npm run build`.

Then seed and link storage:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### Manual equivalent

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

---

## 3. Run

```bash
composer dev
```

Starts the PHP server, the queue worker, log tailing and Vite together.

Or with a local domain (Herd / Valet / a host entry), which is how the project is configured for development:

```bash
npm run dev
```

and open **http://sshs-alumni.test/**

---

## 4. Demo credentials — development only

| Email | Password | Role |
|---|---|---|
| `admin@example.test` | `ChangeMe123!` | Super Admin |
| `member@example.test` | `ChangeMe123!` | Member (approved) |
| `pending@example.test` | `ChangeMe123!` | Member (pending verification) |

> ## ⚠️ Change this password before production
>
> `DemoSeeder` **refuses to run when `APP_ENV=production`**, so these accounts cannot be created on a production deploy. If the database was seeded in development and later promoted, **delete or re-password every demo account first**. See [13-deployment.md](13-deployment.md).

All seeded records carry an `is_demo` marker or a `[DEMO]` name prefix so they can be found and removed:

```bash
php artisan demo:purge
```

---

## 5. Creating a real administrator

```bash
php artisan make:admin
```

Prompts for name, email and password, creates the user, verifies the email and assigns **Super Admin**. This is the supported way to create the first real admin — not editing the database by hand.

---

## 6. Seeded data

`DatabaseSeeder` runs, in order:

| Seeder | Creates | Production-safe |
|---|---|---|
| `RolePermissionSeeder` | 47 permissions, 11 roles | ✅ yes — required |
| `SettingsSeeder` | all setting groups with defaults | ✅ yes — required |
| `BatchSeeder` | SSC 1981 → current year | ✅ yes — required |
| `SchoolHistorySeeder` | milestone **1976 — school journey begins** | ✅ yes — required |
| `JubileeSeeder` | the flagship Golden Jubilee event, `date_status = tba` | ✅ yes — required |
| `MessageTemplateSeeder` | system notification templates | ✅ yes — required |
| `VolunteerTeamSeeder` | the nine standard teams | ✅ yes — required |
| `SponsorshipPackageSeeder` | Title → Custom tiers | ✅ yes — required |
| `DemoSeeder` | demo admins, ~200 members, events, CRM records, committees, volunteers, news, announcements | ❌ **dev only — blocked in production** |

Production seeding runs everything except `DemoSeeder`:

```bash
php artisan db:seed --class=ProductionSeeder
```

---

## 7. Useful commands

```bash
php artisan test --compact           # run the suite
php artisan test --filter=Member     # one group
composer ci:check                    # lint + static analysis + tests (what CI runs)
vendor/bin/pint --dirty              # format changed PHP
composer types:check                 # Larastan level 7
npm run check                        # frontend lint
npm run types:check                  # TypeScript

php artisan route:list --except-vendor
php artisan queue:work               # required for campaigns, exports, image variants
php artisan schedule:work            # required for reminders and scheduled campaigns
php artisan optimize:clear           # after editing lang/ or config/
```

---

## 8. Common first-run problems

| Symptom | Cause | Fix |
|---|---|---|
| `Unable to locate file in Vite manifest` | assets not built | `npm run build` or `npm run dev` |
| `could not find driver` | missing `pdo_sqlite` | enable the extension |
| Image upload fails silently | missing `gd` | enable `gd`, restart PHP |
| Uploaded images 404 | no storage symlink | `php artisan storage:link` |
| Bangla renders as boxes | fonts not built | `npm run build` |
| Campaigns never send | no queue worker | `php artisan queue:work` |
| Translation edits not showing | config/lang cached | `php artisan optimize:clear` |

Fuller coverage in [16-troubleshooting.md](16-troubleshooting.md).
