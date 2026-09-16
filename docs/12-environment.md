# 12 — Environment Variables

Everything the application reads from `.env`. Application code reads `config()`, never `env()` directly, so `php artisan config:cache` works in production.

---

## 1. Application

| Variable              | Dev                       | Production               | Notes                                                       |
| --------------------- | ------------------------- | ------------------------ | ----------------------------------------------------------- |
| `APP_NAME`            | `SSHS Alumni`             | `SSHS Alumni`            | used in mail and page titles                                |
| `APP_ENV`             | `local`                   | `production`             | gates the demo seeder and password rules                    |
| `APP_KEY`             | generated                 | generated                | **rotating invalidates all sessions and encrypted cookies** |
| `APP_DEBUG`           | `true`                    | **`false`**              | non-negotiable — `true` leaks env vars and stack traces     |
| `APP_URL`             | `http://sshs-alumni.test` | `https://yourdomain.org` | used for QR codes, mail links, sitemap                      |
| `APP_LOCALE`          | `bn`                      | `bn`                     | default UI language                                         |
| `APP_FALLBACK_LOCALE` | `en`                      | `en`                     |                                                             |
| `APP_FAKER_LOCALE`    | `en_US`                   | —                        | seeding only                                                |
| `APP_TIMEZONE`        | `Asia/Dhaka`              | `Asia/Dhaka`             |                                                             |

## 2. Database

### Development (SQLite)

```env
DB_CONNECTION=sqlite
# DB_DATABASE defaults to database/database.sqlite
```

### Production (MySQL)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sshs_alumni
DB_USERNAME=sshs_alumni
DB_PASSWORD=<strong-password>
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

`utf8mb4` is **required** — `utf8` in MySQL is 3-byte and cannot store the full Bengali range or emoji.

## 3. Session, cache, queue

| Variable                | Dev        | Production                  |
| ----------------------- | ---------- | --------------------------- |
| `SESSION_DRIVER`        | `database` | `database` (or `redis`)     |
| `SESSION_LIFETIME`      | `120`      | `120`                       |
| `SESSION_ENCRYPT`       | `false`    | **`true`**                  |
| `SESSION_SECURE_COOKIE` | `false`    | **`true`** — requires HTTPS |
| `SESSION_SAME_SITE`     | `lax`      | `lax`                       |
| `CACHE_STORE`           | `database` | `database` (or `redis`)     |
| `QUEUE_CONNECTION`      | `database` | `database` (or `redis`)     |

A queue worker is **required** in production. Without it campaigns, exports and image variants silently never run. See [13-deployment.md](13-deployment.md).

## 4. Filesystem

| Variable          | Dev     | Production        |
| ----------------- | ------- | ----------------- |
| `FILESYSTEM_DISK` | `local` | `local` (or `s3`) |

Two disks are used: `public` for member photos, gallery and logos; `private` for verification documents and sponsor agreements. `private` files are never served by URL — only through an authorized controller. See [08-security-privacy.md §5](08-security-privacy.md).

Uploads: `php.ini` needs `upload_max_filesize=10M`, `post_max_size=12M`.

## 5. Mail

Development uses the log driver — no mail leaves the machine.

```env
MAIL_MAILER=log
```

Production needs a real provider. Sending to thousands of alumni from a shared host's `sendmail` lands in spam.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com        # or SES, Mailgun
MAIL_PORT=587
MAIL_USERNAME=<token>
MAIL_PASSWORD=<token>
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.org
MAIL_FROM_NAME="${APP_NAME}"
```

**Deliverability is a DNS task, not a code task.** SPF, DKIM and DMARC must be configured for the sending domain before the first campaign.

## 6. SMS — BulkSMSBD

```env
SMS_DRIVER=log                        # local/testing — writes to the log, sends nothing
# SMS_DRIVER=bulksmsbd                # production

BULKSMSBD_API_KEY=
BULKSMSBD_SENDER_ID=
BULKSMSBD_BASE_URL=http://bulksmsbd.net/api
BULKSMSBD_TIMEOUT=15
SMS_ENABLED=false                     # master kill switch
SMS_DAILY_CAP=2000                    # spend guard — blocks runaway sends
SMS_COUNTRY_CODE=88

SMS_OTP_BRAND="SSHS Alumni"           # MUST be Latin-script
SMS_OTP_LENGTH=6
SMS_OTP_TTL=300
```

| Variable                         | Meaning                                                                                                                                                                          |
| -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `SMS_DRIVER`                     | `log` or `bulksmsbd`. **`log` is the default in `local` and `testing`, so no test run can spend real balance.**                                                                  |
| `BULKSMSBD_API_KEY`              | from the BulkSMSBD dashboard. Never logged, never sent to the frontend, never written into `campaign_recipients.error`.                                                          |
| `BULKSMSBD_SENDER_ID`            | must be **pre-approved by the vendor**. An unapproved sender ID returns error `1002` and every message fails.                                                                    |
| `BULKSMSBD_BASE_URL`             | vendor endpoints: `/smsapi`, `/smsapimany`, `/getBalanceApi`                                                                                                                     |
| `SMS_ENABLED`                    | hard off switch, independent of the driver                                                                                                                                       |
| `SMS_DAILY_CAP`                  | maximum **segments** per calendar day; exceeding it halts the campaign and alerts an admin                                                                                       |
| `SMS_OTP_BRAND`                  | the brand name inside the vendor-mandated OTP body, `Your {Brand} OTP is XXXX`. **Latin-script only** — a Bangla brand pushes the message to Unicode and the gateway rejects it. |
| `SMS_OTP_LENGTH` / `SMS_OTP_TTL` | OTP digits, and how long a code stays valid (seconds)                                                                                                                            |

**Cost note.** Bangla must be sent as `type=unicode`, and a Unicode SMS segment is 70 characters against 160 for Latin — roughly 2.3x the cost per character. The campaign screen shows the segment count and estimated cost before sending. See [05-modules.md §13](05-modules.md).

## 7. Payments

```env
PAYMENT_GATEWAY=manual
PAYMENT_CURRENCY=BDT
```

Only the manual/offline gateway is implemented. Future gateway keys (bKash, Nagad, SSLCommerz) are added here alongside a new driver class. See [09-payments.md](09-payments.md).

## 8. Organization defaults

Bootstrap values for the first seed. After that, these are edited in `/admin/settings` and stored in the database — the settings row wins.

```env
# The association — organiser of the Jubilee
ORG_NAME_BN="প্রাক্তন ছাত্র-ছাত্রী পরিষদ"
ORG_NAME_EN="Former Students Association"
ORG_ESTABLISHED=2015
ORG_CONTACT_EMAIL=
ORG_CONTACT_PHONE=

# The school — whose fifty years are being celebrated
SCHOOL_NAME_BN="সবুজ শিক্ষায়তন সরকারি উচ্চ বিদ্যালয়"
SCHOOL_NAME_EN="Sabuj Shikshayatan Government High School"
SCHOOL_ESTABLISHED=1976
SCHOOL_EIIN=105070
SCHOOL_ADDRESS="Hafiz Jute Mills Ltd, Baro Aulia, Sitakunda, Chattogram"
SCHOOL_PHONE=01745950025
SCHOOL_EMAIL=sabujshikha@yahoo.com
SCHOOL_WEBSITE=https://sabujsghs.edu.bd
SCHOOL_BOARD_BN="মাধ্যমিক ও উচ্চ মাধ্যমিক শিক্ষা বোর্ড, চট্টগ্রাম"
```

> **Two founding years, deliberately separate.** `SCHOOL_ESTABLISHED=1976` drives the ১৯৭৬–২০২৬ milestone. `ORG_ESTABLISHED=2015` is the association's own founding. Conflating them would misstate both organizations' history. See [00-overview.md §3](00-overview.md).

Head Teacher and সভাপতি names are **not** environment variables — office holders change, and updating them must never require a redeploy. They are seeded into `settings.organization` and edited in the admin panel.

**No Golden Jubilee date variable exists.** The date is set by an administrator in the database. See [17-golden-jubilee.md](17-golden-jubilee.md).

## 9. Logging

| Variable         | Dev     | Production |
| ---------------- | ------- | ---------- |
| `LOG_CHANNEL`    | `stack` | `daily`    |
| `LOG_LEVEL`      | `debug` | `warning`  |
| `LOG_DAILY_DAYS` | —       | `14`       |

Logs must never contain: passwords, API keys, session tokens, two-factor secrets, or full member records.

## 10. Production checklist

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.org
DB_CONNECTION=mysql
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
MAIL_MAILER=smtp
LOG_LEVEL=warning
SMS_DRIVER=bulksmsbd
SMS_ENABLED=true
```

Then:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **`config:cache` freezes `.env`.** After any `.env` change in production, re-run `php artisan config:cache` or the change has no effect.

## 11. Secret handling

| Rule                  |                                                                                          |
| --------------------- | ---------------------------------------------------------------------------------------- |
| `.env` is git-ignored | already configured                                                                       |
| `.env` permissions    | `600`, owned by the web user                                                             |
| `.env` location       | outside the web root                                                                     |
| Rotation              | on any staff departure: `BULKSMSBD_API_KEY`, mail credentials, DB password               |
| `APP_KEY`             | back it up. Losing it makes encrypted data unrecoverable. Rotating it logs everyone out. |
| Never                 | commit, log, expose to the frontend, or include in an exception report                   |
