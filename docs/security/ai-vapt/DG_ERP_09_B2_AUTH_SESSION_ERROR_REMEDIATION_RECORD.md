# DG ERP ⑨-B2 — Authentication, Session & Error-Exposure Remediation Record

Date: 2026-09-10  
Scope: VAPT-004, VAPT-005, and VAPT-006 only

## Findings and root causes

- VAPT-004: login rejected inactive accounts, but protected requests did not re-check the persisted account state. Staff-block actions changed `account_status` without revoking remember tokens or database-backed sessions.
- VAPT-005: the single real `POST /login` endpoint had no failed-attempt limiter.
- VAPT-006: multiple application-controlled catch paths can flash raw exception text. Existing safe domain messages and validation errors must remain actionable, so a blanket replacement of every exception message was not appropriate.

The audit covered `routes/web.php`, authentication and role/company middleware, logout and password-reset flows, session configuration, staff-block controllers, application exception/redirect/JSON response paths, and repository-wide exception-message usage.

## Remediation

- `account.active` now protects both platform and company route groups. It reloads only the persisted account status for the authenticated ID on every protected request. An inactive account is logged out, its current session is invalidated, its CSRF token is regenerated, and the request is denied with the normal login response (or a generic JSON 401).
- Both company-staff and platform-staff block actions revoke the target user's remember token. With the database session driver, all sessions belonging to that user are deleted without affecting other users.
- `POST /login` permits five failed attempts per normalized-identity-plus-IP key during a 60-second decay window. Failed attempts are counted, successful authentication clears the key, and responses do not disclose whether an identity exists.
- Web responses pass through one sanitizer that detects internal diagnostic-shaped messages in the established `error` flash channel and top-level JSON `message`/`error` fields. It returns a generic message and logs only response context, route, company/user IDs, and a SHA-256 fingerprint. It does not log raw exception text, request payloads, credentials, tokens, or session IDs.
- Legitimate validation errors and ordinary safe business messages are unchanged.
- Laravel's never-flash list explicitly includes current password, password confirmation, password, and credential inputs.

## Files changed for B2

- `app/Http/Middleware/EnsureActiveAccount.php`
- `app/Http/Middleware/SanitizeUserFacingErrors.php`
- `app/Services/UserSessionRevocationService.php`
- `app/Services/UserFacingErrorSanitizerService.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/Admin/SuperStaffController.php`
- `bootstrap/app.php`
- `routes/web.php`
- `tests/Feature/AuthenticationSessionErrorSecurityTest.php`
- `tests/Feature/CompanyFactoryResetTest.php` (expected inactive-session response updated)
- `tests/Feature/OpeningBalanceModuleTest.php` (test-only fixture aligned with the real default-active `users.account_status` column)

## Test evidence

- Focused B2: 5 tests / 49 assertions.
- Authentication, authorization, Auditor, country/company isolation, reset, and deletion regression: 55 tests / 525 assertions.
- Compliance gate: 199 tests / 2,914 assertions.
- Safe non-Journal gate: 463 tests / 5,411 assertions.

No assertion was weakened to conceal a defect. The Opening Balance fixture correction added the missing production-equivalent account-state column; its 29 tests pass with the original business assertions.

## Residual risk and deferred work

- Database-session deletion applies when the configured session driver is `database`; other centralized session stores require their deployment-specific revocation design. The per-request persisted-state gate still denies blocked accounts regardless of session driver.
- Reverse-proxy client-IP trust and distributed rate-limiter storage must be verified in a production-equivalent environment.
- Independent external testing should verify concurrent sessions, proxy behavior, JSON variants, and server/log configuration.
- Dependency upgrades, spreadsheet formula injection, security headers/cookies, numbering concurrency, CBMS integration, MFA, and infrastructure controls remain intentionally deferred.

## Git and environment preservation

Testing used isolated SQLite in-memory databases only. No production, staging, external CBMS endpoint, or DGTAS Website was accessed. No files were staged, committed, pushed, deployed, reset, cleaned, or stashed. `tests/Feature/PurchaseLifecycleEndToEndTest.php` remains at 292 staged insertions and 17 unstaged insertions.

Status: **B2 INTERNAL RETEST PASS**. This is not independent VAPT certification.
