# 16 — Troubleshooting

---

## 1. Frontend

### `Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest`

Assets not built.

```bash
npm run dev      # development
npm run build    # production / one-off
```

In production the built assets in `public/build` must be present — either committed or built during deploy.

### Changes not appearing in the browser

1. Is `npm run dev` running?
2. `php artisan optimize:clear`
3. Hard reload (Ctrl+Shift+R) — service worker and long-cache headers.
4. In production: `npm run build` **and** `php artisan view:cache`.

### TypeScript errors after adding a controller

Wayfinder regenerates on build. Run `npm run dev` or `php artisan wayfinder:generate`. Never hand-edit `resources/js/actions/`, `routes/` or `wayfinder/`.

---

## 2. Bangla rendering

### Bengali shows as boxes (□□□) or question marks

| Cause                    | Fix                                                         |
| ------------------------ | ----------------------------------------------------------- |
| Fonts not built          | `npm run build`                                             |
| `lang` attribute missing | `<html lang="{{ app()->getLocale() }}">` in `app.blade.php` |
| Database not `utf8mb4`   | see below                                                   |
| File saved as non-UTF-8  | all `lang/**` files must be UTF-8 without BOM               |

### Bengali corrupted in the database (`à¦¬à¦¾à¦‚à¦²à¦¾`)

MySQL is using `utf8` (3-byte) instead of `utf8mb4`.

```sql
SHOW VARIABLES LIKE 'character_set_database';
```

```env
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

```sql
ALTER DATABASE sshs_alumni
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Existing corrupted rows cannot be repaired by changing the charset — restore from a backup taken **with** `--default-character-set=utf8mb4`.

### Bengali broken in a database backup

`mysqldump` defaults to latin1 on some systems.

```bash
mysqldump --default-character-set=utf8mb4 --single-transaction -u user -p db > backup.sql
```

### Bengali broken in a PDF

**This is the known dompdf limitation flagged throughout the docs.** See [09-payments.md §5](09-payments.md).

1. Embed Noto Sans Bengali:

```php
// config/dompdf.php
'font_dir'   => storage_path('fonts/'),
'font_cache' => storage_path('fonts/'),
'defaultFont' => 'notosansbengali',
```

2. If conjuncts (যুক্তাক্ষর) still render wrong — `ক্ষ`, `ন্ত`, `স্ত` broken apart — dompdf's text shaping cannot handle them. **Do not keep fighting it.** Fall back to:
    - Financial documents in Latin script (already the norm for receipts in Bangladesh)
    - Member-facing bilingual documents via **browser print-to-PDF**, which shapes Bengali correctly

Test this early, in the phase that builds receipts, not at the end.

### Bangla SMS arrives as gibberish

Sent as `type=text` instead of `type=unicode`. `BulkSmsBdChannel` selects the type from the rendered message content — if the message mixes Bangla and Latin, it must still be `unicode`.

Remember a Unicode SMS segment is **70 characters**, not 160.

---

## 3. Database

### `could not find driver`

Missing PDO extension. Enable `pdo_sqlite` (dev) or `pdo_mysql` (prod) and restart PHP-FPM.

### `database is locked` (SQLite)

Concurrent writes. Expected — it is why MySQL is the production target. For development:

```php
// config/database.php, sqlite connection
'journal_mode' => 'WAL',
'busy_timeout' => 5000,
```

If this appears in production, the deployment is on SQLite and should be migrated. See [13-deployment.md §10](13-deployment.md).

### Migration fails on MySQL but passed on SQLite

Almost always one of the portability rules in [01-architecture.md §8](01-architecture.md):

| Error                               | Cause                                                                     |
| ----------------------------------- | ------------------------------------------------------------------------- |
| `Specified key was too long`        | indexed `string` without an explicit short length under `utf8mb4`         |
| `Identifier name is too long`       | auto-generated index name over 64 characters — name it explicitly         |
| `Syntax error near ENUM`            | an SQL `ENUM` slipped in — use `string` + a PHP enum cast                 |
| `Cannot add foreign key constraint` | migration order, or mismatched column types between the FK and its target |

### `Class "Database\Seeders\DemoSeeder" ... refused`

Working as designed. `DemoSeeder` blocks itself when `APP_ENV=production`.

---

## 4. Queues

### Campaigns, exports or image variants never complete

No queue worker running. This fails **silently** — jobs pile up in the `jobs` table with nothing reporting an error.

```bash
php artisan queue:work            # development
supervisorctl status              # production
```

```sql
SELECT COUNT(*) FROM jobs;        -- pending
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

### Jobs run old code after a deploy

Queue workers hold code in memory. **Restart them on every deploy:**

```bash
supervisorctl restart sshs-alumni-worker:*
```

### A job keeps failing

```bash
php artisan queue:failed
php artisan queue:retry <uuid>
php artisan queue:retry all
```

Read the exception in `failed_jobs.exception` before retrying — retrying a deterministic failure just fails again.

---

## 5. Mail

### No email sent in development

`MAIL_MAILER=log` by design. Check `storage/logs/laravel.log`.

### Emails go to spam in production

Not a code problem. Publish **SPF**, **DKIM** and **DMARC** for the sending domain, use a real provider (SES/Postmark/Mailgun), and make sure `MAIL_FROM_ADDRESS` is on a domain you control.

### Mail queued but never delivered

Mail notifications are queued — see §4.

---

## 6. SMS (BulkSMSBD)

| Code          | Meaning                                             | Action                                                                      |
| ------------- | --------------------------------------------------- | --------------------------------------------------------------------------- |
| `202`         | Submitted successfully                              | none — this is success                                                      |
| `1002`        | Sender ID invalid or disabled                       | `BULKSMSBD_SENDER_ID` is wrong or not approved by the vendor. Contact them. |
| `1003`        | Missing required fields                             | a parameter was dropped — a bug, check the request                          |
| `1005`        | Internal error                                      | vendor-side; retry with backoff                                             |
| `1006`        | Balance validity unavailable                        | account issue; contact the vendor                                           |
| `1007`        | **Insufficient balance**                            | top up. The campaign halts rather than burning retries.                     |
| `1011`        | User ID not found                                   | `BULKSMSBD_API_KEY` is wrong                                                |
| `1012`        | Bengali masking required                            | resend as `type=unicode`                                                    |
| `1013`–`1021` | Gateway / pricing / account configuration           | vendor-side configuration; the raw code is surfaced to the admin            |
| **`1032`**    | **IP not whitelisted** — undocumented by the vendor | **halt**; whitelist the server's outbound IP (see below)                    |

### Code 1032 — "Your ip ... not Whitelisted"

**This is not in the vendor's published code table**, which stops at 1021. It is returned when the calling server's outbound IP is not whitelisted on the BulkSMSBD account.

The important trap: **the balance endpoint does not enforce whitelisting, but the send endpoint does.** A successful `php artisan sms:balance` therefore proves the API key works and proves nothing about whether sending will work.

Fix:

1. Find the server's **public** outbound IP — not its LAN address:

```bash
curl -s https://api.ipify.org
```

2. Add it in the BulkSMSBD panel under **Phonebook → whitelist IP**.
3. Re-run `php artisan sms:test <number>`.

Every environment that sends needs its own entry: the developer machine, staging, and production. On a home or office connection the public IP is usually dynamic and will change, so keep SMS sending on the server rather than a workstation.

### No SMS sent at all

Check, in order: `SMS_ENABLED=true` · `SMS_DRIVER=bulksmsbd` (it is `log` by default outside production) · a queue worker is running · `SMS_DAILY_CAP` not already reached.

### OTP messages are rejected or never arrive

BulkSMSBD requires the exact body format:

```
Your {Brand/Company Name} OTP is XXXX
```

Anything else is blocked at the gateway. `Sms\OtpMessage` builds it, so this should not happen — but if it does, check `SMS_OTP_BRAND`:

- It must be **Latin-script**. A Bangla brand makes the message Unicode and breaks the format; `OtpMessage` throws rather than sending.
- It must contain at least one letter or digit after sanitisation (only `A–Z a–z 0–9 space . & -` survive).

Never localise the OTP body. It is the one user-facing string in the platform that stays English by design.

### Numbers are reported invalid before sending

The platform normalises to `88` + the full local 11-digit number (`8801712345678`) and rejects anything that is not `01[3-9]` followed by eight digits. A rejected number is failed locally and never dispatched, so it costs nothing — check the stored number rather than the gateway.

### SMS costs more than expected

Bangla is Unicode: 70 characters per segment, not 160. A 200-character Bangla message is **3 segments**. The campaign screen shows the segment count before sending — check it there.

---

## 7. Files & uploads

### Uploaded images return 404

```bash
php artisan storage:link
```

### Image upload fails silently

`gd` is not installed. `php -m | grep gd`. Install and restart PHP-FPM.

### "The file failed to upload" on large files

```ini
upload_max_filesize = 10M
post_max_size = 12M
```

and in Nginx: `client_max_body_size 12M;`. All three must exceed the largest allowed upload.

### `Permission denied` writing to storage

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 8. Authentication & permissions

### A user with the right role gets 403

Spatie caches permissions.

```bash
php artisan permission:cache-reset
php artisan optimize:clear
```

If it persists, confirm the role actually holds the permission — check `/admin/roles`, not the seeder source.

### Everyone logged out after a deploy

`APP_KEY` changed. Restore the original key. If it is genuinely lost, all sessions and encrypted data are unrecoverable — this is why it must be backed up.

### Email verification links 404 or fail

`APP_URL` does not match the real domain, or `config:cache` was run before `.env` was finalized.

```bash
php artisan config:cache
```

---

## 9. Production configuration

### `.env` changes have no effect

`config:cache` freezes `.env` at cache time.

```bash
php artisan config:cache
```

after **every** `.env` change in production.

### Old code still served after a deploy

OPcache with `validate_timestamps=0`. Reload PHP-FPM:

```bash
systemctl reload php8.3-fpm
```

Add it to the deploy script.

### `.env` is downloadable from the browser

**Critical.** Nginx `root` is pointing at the project root instead of `public/`. Fix immediately, then **rotate every secret in the file** — assume it was read.

---

## 10. Performance

### A page is slow

```bash
php artisan pail                 # live logs
```

Usual causes, in order of likelihood:

1. **N+1 queries** — add `with()` to the controller's query.
2. Missing index on a filter column — see [02-database-schema.md §15](02-database-schema.md).
3. Unpaginated list.
4. Synchronous work that belongs on the queue.
5. Counter cache not being used (a `COUNT(*)` per row).

### The admin dashboard is slow

Charts should be `Inertia::defer`red. If the page blocks, a chart query is running inline.

### The directory is slow at scale

Confirm the query uses `search_blob` and not five OR-ed `LIKE`s. Past ~50,000 members, see the Scout migration in [15-roadmap.md §2](15-roadmap.md).

---

## 11. Getting more detail

```bash
php artisan pail                        # live log stream
tail -f storage/logs/laravel.log
php artisan about                       # environment summary
php artisan route:list --except-vendor
php artisan config:show database
php artisan migrate:status
php artisan queue:failed
```

> **Never set `APP_DEBUG=true` in production to debug an issue.** It exposes environment variables, database credentials and full stack traces to anyone who triggers an error. Reproduce on staging instead.
