# DG ERP Hosting Readiness Audit

Audit date: 2026-08-23

Scope: deployment and hosting readiness only

Excluded and unchanged: accounting logic, financial formulas, stock logic, subscription business logic, and the frozen 102-file migration baseline

## Executive status

Application code readiness: **PASS**

Local production-build readiness: **PASS**

Actual hosting environment readiness: **PENDING HOST CONFIGURATION**

Deployment authorization: **NOT READY until every item in “Hosting acceptance gate” is confirmed**

The application now boots, its production assets build, Composer and npm report no known dependency advisories, and the complete automated suite passes. The remaining items require the real hosting account, domain, TLS certificate, database credentials, SMTP credentials, cron/worker access, and backup destination; they cannot be truthfully marked PASS from localhost.

## Verified platform baseline

| Area | Result | Evidence / requirement |
|---|---|---|
| PHP | PASS locally | PHP 8.4.20; application constraint is PHP `^8.2`. Use supported PHP 8.2–8.4 on hosting. |
| PHP extensions | PASS locally | `ctype`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `json`, `libxml`, `mbstring` or polyfill, `openssl`, `PDO`, `pdo_mysql`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, and `zlib`. Enable `bcmath`, `curl`, and `intl` as operational requirements. |
| Laravel | PASS | Laravel 12.67.0 boots successfully. |
| Composer platform requirements | PASS | `composer check-platform-reqs` completed successfully. |
| PHP dependency security | PASS | `composer audit --locked`: no security vulnerability advisories. |
| JavaScript dependency security | PASS | `npm audit --omit=dev`: zero vulnerabilities. |
| Frontend build | PASS | `npm run build`: 52 modules transformed and a valid Vite manifest generated. |
| Database compatibility | PASS locally | MariaDB 10.4.32 fresh build previously passed. On hosting, use `DB_CONNECTION=mysql` even when the server is MariaDB so the frozen exact-DDL migration path is used. Do not use `DB_CONNECTION=mariadb`. |
| Migration baseline | FROZEN / NOT TOUCHED | 102 final migrations and 102 application tables; this audit made no migration changes and ran no migrations. |
| Health route | PASS | Laravel health endpoint is `/up`. |
| Scheduler | PASS in code | `companies:check-expiry` is scheduled daily at 00:05 from `routes/console.php`. Hosting cron is still required. |
| Storage link | PASS locally | `public/storage` points to `storage/app/public`. It must be recreated on each new hosting release. |
| Full regression suite | PASS | 341 tests passed, 3,862 assertions. |

## Proven defects fixed during this audit

1. Composer lock contained vulnerable dependency versions. The lock was updated within existing version constraints; current audit reports no advisories.
2. Vite referenced missing `resources/css/app.css` and `resources/js/app.js`, making a production asset build impossible. Minimal canonical entry files were restored and the build now passes.
3. `DatabaseSeeder` created `test@example.com` in every environment. It now creates that fixture only in `local` or `testing`.
4. A company approval fallback generated the known password `123456` if a registration password was absent. Approval now fails safely and creates no company or user when the password is missing.
5. Fresh `db:seed` did not create canonical roles or permissions and had no secure first-platform-admin strategy. Seed order now creates roles, permissions, subscription reference data, and a one-time environment-provided platform administrator. No default credentials exist.
6. The obsolete `app/Console/Kernel.php` was plain text without a PHP opening tag and duplicated an outdated schedule. It was removed; Laravel 12 scheduling remains in `routes/console.php`.

## Production environment contract

Start from `.env.production.example`, never from the local `.env`. Required rules:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://<real-domain>`
- generate a unique `APP_KEY` once; back it up securely and never rotate it casually
- `DB_CONNECTION=mysql` for both MySQL and MariaDB hosting
- use a dedicated least-privilege database user, never `root`
- `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`
- `CACHE_STORE=database`
- use real SMTP; `MAIL_MAILER=log` is not acceptable for production password/notification email
- use `LOG_CHANNEL=daily`, an appropriate `LOG_LEVEL`, and monitor `storage/logs`
- provide unique `DG_ERP_ADMIN_NAME`, `DG_ERP_ADMIN_EMAIL`, and `DG_ERP_ADMIN_PASSWORD` (minimum 12 characters) only for first seed; remove them from the live environment after the administrator is created and configuration is recached
- never commit `.env`, credentials, application keys, database dumps, or user uploads

## Web server and filesystem

- The domain document root must be the project’s `public` directory, not the repository root.
- Force HTTPS at the hosting control panel, reverse proxy, or web-server virtual host. Enable automatic certificate renewal.
- Only `storage` and `bootstrap/cache` need application-server write access.
- Run `php artisan storage:link` after deploying a new release.
- The existing application also writes tenant files below `public/companies`. Preserve that directory between releases, grant only the required write permission, and include it in backups. Migrating those legacy public uploads to private object storage is a future hardening item, not a prerequisite code change in this audit.
- Disable directory listing and prevent script execution inside writable upload directories at the hosting/web-server layer.
- If TLS terminates at a proxy/CDN, verify Laravel receives the original HTTPS scheme before enabling traffic; configure trusted proxies for the actual provider only.

## Scheduler, queue, mail, cache, and session

Required cron entry (adapt paths and PHP binary):

```cron
* * * * * cd /absolute/path/to/dg_erp && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

The codebase currently sends mail synchronously and no application job implementing `ShouldQueue` was found, so a queue worker is not required for current core behavior. If `QUEUE_CONNECTION=database` is retained for future queued work, run a supervised worker and restart it after every deployment:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
php artisan queue:restart
```

Before go-live, send a real test email, confirm database-backed cache/session operations, confirm scheduler execution in logs, and confirm `/up` returns HTTP 200 over HTTPS.

## First deployment procedure

1. Create a release directory and point the domain document root to its `public` directory.
2. Install the exact lock-file dependencies:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   npm ci
   npm run build
   ```

3. Create production `.env` from `.env.production.example`, replace every placeholder, then run `php artisan key:generate` only for this first deployment.
4. Verify before database mutation:

   ```bash
   composer check-platform-reqs
   composer audit --locked
   php artisan about
   php artisan config:show database
   ```

5. Confirm `production`, debug disabled, HTTPS URL, and `mysql` connection. Back up the empty/new database if required by host policy.
6. Run the frozen baseline and canonical seed exactly once:

   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

7. Sign in with the environment-provided platform admin, change/confirm the password, remove the three `DG_ERP_ADMIN_*` values, then run:

   ```bash
   php artisan config:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan storage:link
   ```

8. Configure cron, and configure Supervisor/systemd only if using an asynchronous queue.
9. Smoke-test `/up`, login/logout, registration approval, one authorized company screen, one denied staff action, upload/download, mail, and scheduler execution.
10. Enable traffic only after the hosting acceptance gate passes.

For later deployments, take backups first, enable maintenance mode, deploy the exact committed lock files, run `php artisan migrate --force` only when an approved future migration exists, rebuild caches, restart workers, smoke-test, then disable maintenance mode. Never run `migrate:fresh`, `db:wipe`, or destructive reset commands on hosting.

## Backup and rollback procedure

Before every release, capture and verify:

- a transactional database dump
- `storage/app/public`
- `public/companies`
- the current production `.env` and `APP_KEY` in an encrypted secret backup
- the currently deployed commit/release identifier

Store backups encrypted and off-server, define retention, and perform a restore drill before go-live. A backup is not accepted until restoration is tested.

Rollback application code by switching the web root/release symlink to the previous release, clearing/rebuilding Laravel caches, and restarting workers. Because database rollbacks can destroy business data, do not use `migrate:rollback` automatically. If a release changes schema incompatibly, restore the verified pre-deploy database and upload backups under an approved outage procedure.

## Hosting acceptance gate

All boxes must be confirmed on the real server:

- [ ] Hosting PHP version and every required extension verified
- [ ] MySQL/MariaDB version and `DB_CONNECTION=mysql` verified
- [ ] Domain document root is `/public`
- [ ] Valid HTTPS certificate, forced HTTPS, and renewal verified
- [ ] Production `.env` contains no placeholders; debug is false
- [ ] Dedicated database credentials and least privilege verified
- [ ] Writable-directory permissions verified; no broad `777`
- [ ] `public/storage` link and persistent `public/companies` directory verified
- [ ] SMTP delivery test passed
- [ ] Database cache and session tests passed
- [ ] Scheduler cron executed successfully
- [ ] Queue decision recorded; supervised worker verified if enabled
- [ ] Encrypted off-server database/upload backup completed and restored in a drill
- [ ] Secure platform admin created; bootstrap secrets removed
- [ ] Production assets, config cache, route cache, and view cache built
- [ ] `/up` and post-deploy smoke tests passed over HTTPS
- [ ] Previous release and tested database/upload rollback artifacts available

## Final decision

HOSTING READINESS AUDIT: **APPLICATION PASS / HOST PENDING**

DEPLOYMENT STATUS: **NOT READY**

Exact blockers:

1. Real hosting provider/server specifications have not yet been supplied or verified.
2. Production domain and HTTPS certificate are not yet verified.
3. Production database, SMTP, and platform-admin secrets are not yet configured.
4. Hosting filesystem permissions, persistent upload paths, cron, and optional worker have not yet been verified.
5. Encrypted off-server backup destination and successful restore drill are not yet verified.

Once those five host-side blockers are cleared and the acceptance gate is signed off, deployment may proceed using this runbook.
