# 13 — Deployment

Target: a standard Linux server with PHP-FPM, Nginx (or Apache) and MySQL. **Docker is not required.**

---

## 1. Server requirements

| Component | Minimum | Recommended |
|---|---|---|
| OS | Ubuntu 22.04 / Debian 12 / AlmaLinux 9 | Ubuntu 24.04 LTS |
| PHP | 8.3 | 8.3 / 8.4 with OPcache |
| Web server | Nginx 1.22 | Nginx |
| Database | **MySQL 8.0** | MySQL 8.0 / MariaDB 10.11 |
| Node | 20 (build only) | 22 LTS |
| RAM | 2 GB | 4 GB (event-day traffic) |
| Disk | 20 GB | 50 GB — photos and gallery grow steadily |
| Process supervisor | supervisor / systemd | |
| TLS | Let's Encrypt | |

Extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `bcmath`, `gd`, `zip`, `curl`, `opcache`.

## 2. Why MySQL in production

SQLite is excellent for development and stays the development default. It is the wrong choice for this platform in production:

- **SQLite serializes writes.** One writer at a time, database-wide. At the Golden Jubilee gate — hundreds of check-ins, registrations and payments in the same minutes — writes queue behind each other and time out.
- Concurrency at the target scale (10k–50k members, 500–2,000 concurrent on event day) is exactly the workload SQLite is not built for.
- Backup of a live SQLite file under write load requires care; `mysqldump` is routine.

Migrations are written portably and CI runs the suite on both drivers, so the difference never becomes a code difference. See [01-architecture.md §8](01-architecture.md).

## 3. First deployment

```bash
# 1. Code
cd /var/www
git clone <repo-url> sshs-alumni
cd sshs-alumni

# 2. PHP dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# 3. Environment
cp .env.example .env
nano .env                 # see docs/12-environment.md
php artisan key:generate

# 4. Database
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force

# 5. First administrator
php artisan make:admin

# 6. Storage
php artisan storage:link

# 7. Frontend
npm ci
npm run build

# 8. Caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Permissions

```bash
chown -R www-data:www-data /var/www/sshs-alumni
find /var/www/sshs-alumni -type f -exec chmod 644 {} \;
find /var/www/sshs-alumni -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
chmod 600 .env
```

Only `storage/` and `bootstrap/cache/` are writable by the web user. Nothing else.

## 4. Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.org www.yourdomain.org;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.org www.yourdomain.org;

    root /var/www/sshs-alumni/public;   # public/ only — never the project root
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.org/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    client_max_body_size 12M;           # must exceed the largest upload (10M)
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;       # report exports
    }

    location ~ /\.(?!well-known).* { deny all; }   # blocks .env, .git

    location ~* \.(css|js|jpg|jpeg|png|gif|webp|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
```

`root` points at `public/`. If it points at the project root, `.env` becomes downloadable.

### Apache

`public/.htaccess` ships with Laravel and works unchanged. Set `DocumentRoot` to `public/`, enable `mod_rewrite`, and set `AllowOverride All`.

## 5. Queue worker — required

Campaigns, exports, image variants and counter recalculations run on the queue. **Without a worker they never run, and nothing reports an error.**

```ini
# /etc/supervisor/conf.d/sshs-alumni-worker.conf
[program:sshs-alumni-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sshs-alumni/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sshs-alumni/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start sshs-alumni-worker:*
```

`--max-time=3600` recycles workers hourly so a long-lived process cannot hold stale code after a deploy.

## 6. Scheduler — required

Event reminders and scheduled campaigns depend on it.

```cron
* * * * * cd /var/www/sshs-alumni && php artisan schedule:run >> /dev/null 2>&1
```

One entry. Laravel dispatches everything else internally.

## 7. HTTPS

```bash
certbot --nginx -d yourdomain.org -d www.yourdomain.org
```

Auto-renewal is installed by certbot. Confirm with `certbot renew --dry-run`.

Set `APP_URL=https://…` **before** generating any QR codes — a membership card QR printed with an `http://` URL is wrong forever.

## 8. Redeploying

```bash
cd /var/www/sshs-alumni
php artisan down --render="errors::503"

git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

supervisorctl restart sshs-alumni-worker:*   # workers must restart to load new code
php artisan up
```

> **Always restart the queue workers.** A running worker holds the old code in memory indefinitely.

## 9. Backup

Three things must be backed up. Losing any one of them loses data the others cannot restore.

| What | How | Frequency |
|---|---|---|
| Database | `mysqldump` | daily, 30-day retention |
| `storage/app` | `rsync` / `tar` — photos, documents, gallery | daily |
| `.env` | manual, stored securely offline | on every change |

```bash
#!/bin/bash
# /usr/local/bin/sshs-backup.sh
set -euo pipefail
DIR=/var/backups/sshs-alumni
STAMP=$(date +%F-%H%M)
mkdir -p "$DIR"

mysqldump --single-transaction --quick --default-character-set=utf8mb4 \
  -u sshs_alumni -p"$DB_PASS" sshs_alumni | gzip > "$DIR/db-$STAMP.sql.gz"

tar czf "$DIR/storage-$STAMP.tar.gz" -C /var/www/sshs-alumni storage/app

find "$DIR" -name '*.gz' -mtime +30 -delete
```

```cron
0 2 * * * /usr/local/bin/sshs-backup.sh
```

`--single-transaction` avoids locking tables during the dump. `--default-character-set=utf8mb4` is required or Bengali text is corrupted in the backup.

**Copy backups off the server.** A backup on the same disk protects against nothing.

### Restore drill

Test the restore on a staging server at least once before the Golden Jubilee. An untested backup is a hypothesis.

## 10. SQLite → MySQL migration

If development data must be promoted, or a first deployment ran on SQLite:

```bash
# 1. Back up the SQLite file
cp database/database.sqlite database/database.sqlite.bak

# 2. Create the MySQL database
mysql -u root -p -e "CREATE DATABASE sshs_alumni
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Point .env at MySQL, then build the schema from migrations
php artisan migrate --force

# 4. Transfer the data
php artisan db:transfer --from=sqlite --to=mysql
```

`db:transfer` is a project command that reads each table in chunks and inserts into the target with foreign key checks deferred, in dependency order.

**Because migrations are the source of truth for both drivers, the schema is identical.** Only rows move. Verify afterwards:

```bash
php artisan db:verify-transfer      # row counts per table, both connections
php artisan test --compact          # against MySQL
```

Check specifically: Bengali text renders correctly, decimal amounts are exact, timestamps kept their timezone, and boolean columns did not become `0`/`1` strings.

## 11. Performance

```ini
; /etc/php/8.3/fpm/conf.d/99-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0     ; production — reload PHP-FPM after deploy
```

`validate_timestamps=0` means **PHP-FPM must be reloaded on every deploy** or the old code keeps serving.

MySQL: `innodb_buffer_pool_size` ≈ 50–70% of available RAM.

Optional Redis for cache and queue once membership passes ~10,000 — `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`. No code change.

## 12. Go-live checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` is the final HTTPS domain — **before** any QR code is generated
- [ ] MySQL with `utf8mb4`
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`
- [ ] Nginx `root` is `public/`; `.env` returns 403
- [ ] HTTPS with auto-renewal verified
- [ ] Queue worker running under supervisor
- [ ] Scheduler cron entry installed
- [ ] `storage:link` created; uploads work end to end
- [ ] Mail provider configured; SPF, DKIM and DMARC published; test mail received
- [ ] `BULKSMSBD_SENDER_ID` approved by the vendor; `SMS_DAILY_CAP` set; test SMS received
- [ ] **The production server's outbound IP whitelisted in the BulkSMSBD panel** (Phonebook). The send endpoint enforces this, the balance endpoint does not — so a working balance check is NOT proof that sending works. Verify with `php artisan sms:test <number>` from the server itself.
- [ ] **Every demo account deleted or re-passworded** (`php artisan demo:purge`)
- [ ] Real Super Admin created via `php artisan make:admin`
- [ ] Official logo, favicon and cover installed at `public/brand/`
- [ ] Privacy policy and terms pages published
- [ ] Backup script installed, scheduled, and a restore drill completed
- [ ] `config:cache route:cache view:cache event:cache` run
- [ ] OPcache enabled; PHP-FPM reload is part of the deploy script
- [ ] Error monitoring configured; `LOG_LEVEL=warning`
- [ ] Load test the check-in endpoint before event day
