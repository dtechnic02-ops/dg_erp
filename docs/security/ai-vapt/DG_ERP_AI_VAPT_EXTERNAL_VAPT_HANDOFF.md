# DG ERP — External VAPT Handoff

## Application

Internet-facing Laravel multi-company ERP handling accounting, inventory, HR/CRM, Sales/Purchase, payments, reports, and Nepal fiscal evidence. Internal AI review is not a VAPT certificate.

## Required test environment

- Dedicated disposable environment with synthetic data and production-equivalent web server/database/session/queue/storage configuration.
- At least two active companies in different countries, plus Nepal CBMS ON/OFF cases.
- Accounts: unauthenticated, Company Staff with limited/full permissions, Auditor, Company Admin, Country Admin, Super Staff, and Global Super Admin.
- Explicit authorization before upload polyglot execution tests, concurrency/load tests, malware samples, or destructive reset/delete scenarios.

## Priority scenarios

- Public upload execution and static cross-tenant file access.
- Session use after block/role change/password reset and concurrent sessions.
- Login brute force, enumeration, reset OTP/replay, CSRF, fixation, cookie/header posture.
- Cross-company IDOR/BOLA across every document, account, file, report, print, export, and AJAX endpoint.
- Financial replay, overpayment, duplicate posting, cancellation, return residuals, account ownership, and concurrency.
- Fiscal snapshot mutation, forced CBMS OFF, FY/reset/delete, audit deletion, Original/Reprint races, and Credit Note over-return.
- Stored/reflected/DOM XSS, spreadsheet formulas, SQLi, path traversal, malicious uploads/downloads, and error leakage.
- Origin/Cloudflare/proxy headers, TLS, backups, logs, monitoring, and secret management.

## Restrictions

No destructive production testing. Do not call IRD `/api/bill` or `/api/billreturn`; CBMS mapping/transport is blocked pending written IRD clarification. Do not use real taxpayer/customer data or credentials.

## Evidence available

- `docs/security/ai-vapt/` internal findings and attack surface.
- `docs/ird/` technical/compliance documentation.
- `docs/ird/evidence/` synthetic fiscal evidence.
- Compliance gate: 199 tests / 2,907 assertions.
- Safe non-Journal gate: 453 tests / 5,332 assertions.

## Retest expectation

Retest all P0/P1 findings, then repeat tenant, auth/session, upload, financial, fiscal, dependency, and infrastructure controls. Report confirmed fixes and residual risk independently.
