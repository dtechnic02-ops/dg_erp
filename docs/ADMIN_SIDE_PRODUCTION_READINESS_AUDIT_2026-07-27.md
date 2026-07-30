# DG ERP — Admin Side Production Readiness Audit

**Audit date:** 2026-07-27  
**Scope:** Admin / Platform side only  
**Method:** Read-only source, route, migration-status, configuration, database metadata, and syntax audit. No source, database, cache, email, payment, or environment value was changed.

## 1. Executive conclusion

**NOT PRODUCTION READY**

The Admin side has four release blockers: the running environment is local/debug/HTTP, one intended security migration is pending, User Management exposes state-changing actions through GET routes, and password reset still uses a hard-coded password without the frozen OTP workflow.

### Security remediation update — 2026-07-27

The approved **User Management** remediation has been completed and verified:

- User Block and Unblock are now CSRF-protected POST actions.
- User Password Reset now starts a hashed, six-digit OTP workflow sent to the authenticated Super Admin email.
- OTPs expire after 10 minutes, are single-use, allow a maximum of five verification attempts, and a new request invalidates the previous request for the same admin/target pair.
- OTP verification is required before a strong confirmed password can be saved; no fixed password is exposed or stored.
- The affected User receives a password-reset notice that contains no password.

This update resolves the **User Management** GET/CSRF and fixed-password findings. The existing Company Management password-reset route remains outside the approved remediation scope and still requires its own approved OTP migration before public hosting.

## 2. Files audited

- `routes/web.php`
- `bootstrap/app.php`
- `app/Http/Kernel.php`
- `app/Models/User.php`, `Role.php`, `Permission.php`
- `app/Services/LoginRedirectService.php`
- `app/Services/PlatformAuthorizationService.php`
- `app/Services/PlatformSettingService.php`
- All `app/Http/Controllers/Admin/*.php`
- Platform Settings Form Requests
- Admin authorization concerns and middleware
- `resources/views/admin/**`
- `public/assets/company/css/common.css`
- `public/assets/company/js/dg.js`
- `config/app.php`, `auth.php`, `session.php`, `cache.php`, `queue.php`, `mail.php`, `filesystems.php`, `logging.php`
- Admin/platform-related migrations and the available test list

## 3. Safe checks run

- PHP lint for Admin controllers, platform Form Requests, models, services, and routes: **PASS**
- Admin route inventory and middleware review: **COMPLETED**
- Duplicate named-route check: **NONE FOUND**
- Migration status: **ONE PENDING MIGRATION FOUND**
- Platform permission metadata query: **COMPLETED**
- Runtime configuration inspection without exposing secrets: **COMPLETED**
- Available automated-test inventory: **COMPLETED**
- Debug statement, inline-style, secret-rendering, and unsafe-route static scans: **COMPLETED**

`view:cache`, migration execution, cache clearing, SMTP testing, payment calls, and browser write actions were intentionally not run because this is a strict read-only audit.

## 4. Passed areas

- Admin outer route group requires authenticated role `1` or `4`.
- `admin.dashboard`, Platform Settings, Users Management, and Super Staff CRUD each have Super Admin-only enforcement.
- Super Staff login redirect is permission-aware and does not send Super Staff to `admin.dashboard`.
- No-permission Super Staff fallback exists at `admin/no-access`.
- Super Staff platform authorization uses direct `user_permissions`, platform scope, and an allow-list.
- Current database metadata: **10 platform permissions**, **113 company permissions**, **0 Super Staff `permission_role` rows**, and **9 Super Staff direct permission rows**.
- No duplicate named routes were found.
- No debug helper was found in active Admin controllers/services/routes.
- Platform Settings secret fields use encrypted casts and hidden model attributes.
- SMTP password, gateway secret key, and webhook secret inputs render blank; only configured/not-configured status is shown.
- Platform Settings file requests validate image/favicon types and sizes; storage replacement saves the new file before removing the old one.
- Public storage link exists.
- Login regenerates session ID; logout invalidates the session and regenerates the CSRF token.
- Login timestamp completion updates `login_at`, `last_seen`, and clears `logout_at` only after an active successful login; logout writes `logout_at` before logout.

## 5. Findings

| Severity | File / area | Exact problem | Risk | Recommended fix | Required before hosting |
|---|---|---|---|---|---|
| **BLOCKER** | Runtime configuration | Active configuration is `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://dg.test`, session/cache drivers are `file`, and `SESSION_SECURE_COOKIE` is unset. | Debug pages can expose exception details; HTTP can expose session cookies; local file drivers are unsuitable for multi-worker production. | Set production environment values: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, secure session cookie, production session/cache driver, and deploy configuration cache only after values are verified. | **Yes** |
| **BLOCKER** | `database/migrations/2026_07_26_000000_remove_super_staff_role_permissions.php` | Migration status shows this migration as **Pending**. | Deployment state is incomplete and environments can diverge. | Back up the production database, review the migration, then run the normal deployment migration procedure. Current local metadata already has zero Super Staff role-permission rows. | **Yes** |
| **BLOCKER** | `routes/web.php`, `Admin\\UserController` | `GET /admin/user/block/{id}`, `GET /admin/user/unblock/{id}`, and `GET /admin/user/reset/{id}` change account state. GET requests do not receive CSRF protection. | A logged-in Super Admin can be induced to block, unblock, or reset an account by visiting a malicious link. | Change only the state-changing routes/actions to POST/PUT with CSRF-protected forms or modal forms; preserve existing business checks. | **Yes** |
| **BLOCKER** | `Admin\\UserController::reset`, `Admin\\CompanyController::resetPassword` | Password reset writes the known password `123456`; it does not use the frozen OTP verification workflow. | Account takeover risk and direct violation of the approved password-reset security rule. | Implement the approved OTP-gated reset workflow before public hosting; never expose or use a fixed default password. | **Yes** |
| **HIGH** | `bootstrap/app.php`, `UpdateLastSeen`, `Admin\\DashboardController` | `UpdateLastSeen` is appended globally and performs a database update on every authenticated request. The Admin dashboard also writes `last_seen` itself, yielding an extra write on dashboard visits. | Avoidable database write load; traffic growth can create contention and unnecessary timestamp churn. | Throttle last-seen updates (for example, only after a defined interval) and retain a single update path. | **Yes, before scale/public launch** |
| **MEDIUM** | `app/Models/User.php` | `login_at`, `logout_at`, and `last_seen` do not have `datetime` casts. | Consumers can receive strings rather than date objects; formatting becomes inconsistent outside the current Blade page. | Add standard datetime casts in a separately approved model-only change. | Recommended |
| **MEDIUM** | Platform Settings singleton | `PlatformSettingService::settings()` uses `firstOrCreate([])`, but the table has no database singleton constraint. | Concurrent first access or manual data changes can create more than one settings row. | Add an approved singleton enforcement strategy/migration before multi-node deployment. | Recommended |
| **MEDIUM** | `app/Http/Kernel.php` | Legacy file contains `dd('middleware working');` outside the class. Laravel currently boots from `bootstrap/app.php`, so the file is not on the active path. | If legacy kernel loading is reintroduced, production requests can halt with debug output. | Remove or retire the unused debug legacy kernel only with explicit approval. | Recommended |
| **MEDIUM** | Legacy Admin Blade files | `admin/invoice.blade.php`, `manual_payment.blade.php`, `payments.blade.php`, `permissions.blade.php`, and `plans.blade.php` contain inline CSS, outside the frozen Bootstrap-first/DG common CSS standard. | Inconsistent responsive behavior and future UI maintenance risk. | Confirm which legacy pages remain reachable, then standardize only approved active pages using existing DG classes. | Recommended |
| **INFORMATIONAL** | Platform Settings SMTP test | The protected Super Admin test route can send a real email only when an active SMTP configuration and password exist. It was not invoked in this audit. | Controlled operational capability; no secret is exposed by the UI. | Keep SMTP testing disabled operationally until live mail is explicitly approved and configured. | No code change required now |
| **INFORMATIONAL** | Payment gateways | Static review found settings storage only; no payment API call or webhook route was introduced by the Platform Settings module. | No current live gateway execution path was observed. | Keep live credentials absent until gateway integration is separately approved. | No code change required now |

#### Resolved findings — User Management remediation update

| Status | File / area | Resolution | Verification |
|---|---|---|---|
| **RESOLVED** | `routes/web.php`, `Admin\\UserController`, `resources/views/admin/users.blade.php` | User Block, Unblock, and Password Reset are now POST actions submitted by forms containing `@csrf`. The routes use `Admin\\UserController` for block/unblock. | Route inventory confirms POST-only state actions; old GET state routes are absent. |
| **RESOLVED** | `Admin\\UserController`, `AdminUserPasswordResetOtpService`, Form Requests, Mailables, reset views | Fixed `123456` reset was replaced for User Management by OTP issue, verification, and strong confirmed password completion. OTP is hashed in server-side cache and never rendered or logged. | Cache-only functional check returned `verified`, then `used`; the fifth wrong attempt returned `locked`. PHP and Blade checks passed. |

## 6. Authentication and redirect result

- Login uses `Auth::attempt`, session regeneration, active-account enforcement, and the existing redirect service.
- Failed credential attempts do not reach timestamp updates.
- Active successful login updates `login_at` and `last_seen`, then clears the prior `logout_at`.
- Logout records `logout_at`, logs out, invalidates the session, and regenerates the CSRF token.
- Super Admin redirects to `admin.dashboard`.
- Super Staff resolves the first authorized platform destination in stable priority order; otherwise it receives the no-access page.
- Company-role redirect code was not changed by this audit.
- **Remediation update:** User Management no longer has GET state-changing actions and now requires OTP verification before password completion. Company Management password reset remains a separate out-of-scope fixed-password flow.

## 7. Authorization result

- Super Admin has the intended implicit platform access.
- Super Staff uses direct allowed `user_permissions` with `scope=platform`; it does not inherit `permission_role` platform access.
- Platform authorization is enforced in the active Companies, Registrations, Subscriptions, Subscription Payments, and Subscription Reports controllers; sidebar visibility is supplementary only.
- Users Management, Super Staff Management, Platform Settings, and Admin Dashboard have Super Admin-only controller/route checks.
- Company permissions, job-role menu visibility, and subscription availability were not modified or expanded by this audit.
- **Outstanding deployment blocker:** the cleanup migration is pending even though current Super Staff role-permission rows are zero.

## 8. Platform Settings security result

- Super Admin-only route group, controller checks, and Form Request authorization: **PASS**.
- SMTP/gateway secrets are encrypted at rest and hidden from model serialization: **PASS**.
- Secret inputs do not render stored secret values: **PASS**.
- Empty secret input preserves existing secret values: **PASS**.
- Branding uploads are constrained to validated image/favicon types and new files are saved before old file removal: **PASS**.
- No real SMTP or payment test was run: **NOT TESTED BY DESIGN**.
- Singleton storage has no database-level one-row guarantee: **MEDIUM finding**.

## 9. Database and migration result

- Admin/platform migrations reviewed include user permissions, permission scope, Super Staff role-permission cleanup, and Platform Settings tables.
- Platform Settings tables are present; secrets use encrypted casts.
- `user_permissions` has foreign keys and a composite unique constraint.
- `platform_social_links` and platform payment gateways have relevant unique constraints.
- **Pending:** `2026_07_26_000000_remove_super_staff_role_permissions`.
- No migration, seed, database record, or table was changed by this audit.

## 10. UI and responsiveness result

- Active Admin layout uses shared Bootstrap and `common.css` DG classes.
- Sidebar menu visibility uses `PlatformAuthorizationService` for Super Staff and Super Admin checks for restricted tools.
- User Details page follows the reusable DG record/A4 print pattern and safely displays missing login values.
- Static scan found inline CSS in legacy Admin views listed in the findings table.
- Desktop/tablet/mobile browser interaction was **not executed** in this read-only audit; no authenticated browser session was used.

## 11. Performance result

- Admin user list eager-loads company relation and paginates: **PASS**.
- User detail eagerly loads company and role: **PASS**.
- Platform Settings public cache does not cache secrets: **PASS**.
- Platform permission checks issue direct permission existence queries; acceptable for protected page checks but should be monitored under load.
- `last_seen` creates an authenticated-request write and a dashboard duplicate write: **HIGH finding**.

## 12. Production environment checklist

Before hosting, Business Owner / deployment operator must verify:

- [ ] Database backup tested and restore procedure documented.
- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] Valid production `APP_KEY` retained securely.
- [ ] HTTPS production `APP_URL` configured.
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, and suitable `SESSION_SAME_SITE` configured.
- [ ] Production session/cache drivers selected (database/Redis as appropriate), not local file storage for multi-worker hosting.
- [ ] Queue driver and worker/supervisor configured if queued work is enabled.
- [ ] Scheduler/cron configured if scheduled commands are used.
- [ ] Mail remains `log`/disabled until live SMTP is deliberately configured and tested.
- [ ] Live payment secrets remain absent until gateway integration approval.
- [ ] `public/storage` link exists (local check: present) and storage/cache/log directories have least-privilege write permissions.
- [ ] Trusted proxy and HTTPS termination settings verified with the hosting provider.
- [ ] HSTS and security headers configured at the web-server/proxy layer after HTTPS is confirmed.
- [ ] Log rotation/retention configured.
- [ ] Custom production error pages verified with debug disabled.
- [ ] Maintenance-mode and normal migration deployment procedures documented.
- [ ] Pending migration reviewed, backed up, and applied during deployment.

## 13. Tests unavailable or not run

- Only default example PHPUnit tests are present; no Admin authentication, redirect, authorization, Platform Settings, or timestamp feature tests exist.
- Browser responsiveness and authenticated action testing were not run because this audit is read-only and no test account/session was altered.
- SMTP, payment, webhook, migration execution, rollback, cache clearing, and database writes were intentionally not run.
- `composer audit` and npm audit were not run because they may require external network access and are not necessary for this source-level read-only result.

## 14. Final blocker list

1. Deploy with production-safe environment/session configuration; current runtime is local/debug/HTTP.
2. Review, back up, and apply pending Super Staff role-permission cleanup migration.
3. Replace the remaining Company Management fixed-password reset behavior with the frozen OTP password-reset workflow.

## 15. Exact next action recommendation

**Do not host yet.**

Obtain Business Owner approval for the remaining Company Management password-reset remediation and deployment configuration/migration work. After those fixes, run a separate authenticated staging verification for login/logout timestamps, Super Admin/Super Staff access, User Management actions, Company Management reset, and production HTTPS/session configuration.
