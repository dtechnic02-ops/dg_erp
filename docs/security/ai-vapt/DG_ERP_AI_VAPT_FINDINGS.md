# DG ERP AI VAPT — Detailed Findings

All evidence is repository-local. “Confirmed” means the vulnerable application condition is directly present; exploit execution against a deployed server was not performed.

## VAPT-001 — Public upload may retain an executable client extension

**Remediation status (2026-09-10): REMEDIATED — INTERNAL RETEST PASS.** New protected uploads use private storage, server-generated UUID names, and extensions derived from detected MIME. Focused executable-filename tests pass. Four unlinked historical files were checksum-verified and moved to a non-routable private quarantine without assigning company ownership. `REQUIRES CONTROLLED PRODUCTION VERIFICATION` for origin/web-server rule enforcement.

- **Severity / confidence:** Critical / High
- **CWE / OWASP:** CWE-434; A05 Security Misconfiguration, A03 Injection
- **Affected:** `FileUploadService::uploadFile`, Loan/Party/financial/document upload callers; public `companies/` paths
- **Prerequisite:** Authenticated user with an upload-capable permission; PHP-capable web server for the destination
- **Scenario:** A polyglot file passes content/MIME validation as an allowed PDF/image while its client filename ends in a server-executable extension. The shared service appends `getClientOriginalExtension()` and writes it below `public/`; Loan/Party paths also preserve client names/extensions.
- **Impact:** Potential remote code execution, full tenant escape, financial/fiscal data compromise, credential theft, and server takeover.
- **Isolation / financial / IRD:** Complete isolation bypass; arbitrary ledger/fiscal manipulation becomes possible; blocks VAPT and IRD readiness.
- **Evidence:** `app/Services/FileUploadService.php:89-96`; `LoanAccountController.php:296-307`; `PartyAccountController.php:161-185`.
- **Mitigation present:** File-size and MIME/image validation in callers; random/time prefixes.
- **Recommendation:** P0—store non-publicly, generate server-owned filenames/extensions from trusted MIME, deny execution at storage layer, and migrate/quarantine existing uploads.
- **Regression test:** Polyglot/client-extension upload cannot create an executable/public file; authorized download still works.
- **Blocks:** External VAPT **Yes**; IRD readiness **Yes**.

## VAPT-002 — Sensitive company attachments bypass authenticated delivery

**Remediation status (2026-09-10): REMEDIATED — INTERNAL RETEST PASS.** Protected record files are served by an authenticated, permission-checked, company-scoped endpoint using authoritative database paths. Cross-company, anonymous, unauthorized-Staff, Auditor-read-only, field-manipulation, and traversal tests pass. Unlinked historical files are private quarantine evidence and remain manual records-review items.

- **Severity / confidence:** High / Confirmed
- **CWE / OWASP:** CWE-200, CWE-862; A01 Broken Access Control
- **Affected:** Employee CV/ID/contracts, loans, payments, party documents, returns, and other files under `public/companies` or public storage
- **Prerequisite:** Knowledge/guess/disclosure of a file URL; no application session required for static files
- **Scenario:** Files are written under the webroot or public storage link. Apache serves existing files before Laravel middleware, so company and Auditor authorization never runs.
- **Impact:** Personal, identity, contractual, and financial document disclosure across tenants or to unauthenticated users.
- **Isolation / financial / IRD:** Tenant confidentiality failure; financial/identity leakage; blocks security readiness.
- **Evidence:** public-path writes throughout upload controllers/services; `public/.htaccess` forwards only non-files; local public company/storage files exist.
- **Mitigation present:** Some filenames include random values; database screens are company-scoped.
- **Recommendation:** P0—private storage plus authorized, company-scoped streaming/download endpoints; short-lived signed URLs only where justified.
- **Regression test:** Anonymous/foreign-company direct access fails; owner with permission succeeds.
- **Blocks:** External VAPT **Yes**; IRD readiness **Yes**.

## VAPT-003 — Tracked public diagnostic and personal-information files

**Remediation status (2026-09-10): REMEDIATED — INTERNAL RETEST PASS.** The three identified public artifacts were removed without reproducing their contents. A recursive automated public-webroot guard now permits only the Laravel entry point and rejects `phpinfo()` diagnostics.

- **Severity / confidence:** High / Confirmed
- **CWE / OWASP:** CWE-200; A05 Security Misconfiguration
- **Affected:** `public/info.php`, `public/test.php`, `public/info.txt`
- **Prerequisite:** HTTP access to known/common filenames
- **Scenario:** Two tracked scripts execute `phpinfo()`, exposing runtime/server configuration. A tracked text file discloses a personal email, mobile number, and address; values are redacted from this report.
- **Impact:** Reconnaissance, configuration leakage, targeted attacks, and privacy exposure.
- **Isolation / financial / IRD:** No direct ledger mutation, but materially lowers attack cost and harms privacy/compliance.
- **Mitigation present:** None in repository webroot rules.
- **Recommendation:** P0—remove from deployable webroot/history as appropriate; deny diagnostic endpoints in server/CD pipeline.
- **Regression test:** deployment manifest/security smoke test returns 404 for diagnostic filenames.
- **Blocks:** External VAPT **Yes**; IRD readiness **Yes**.

## VAPT-004 — Blocked users retain existing authenticated sessions

- **Severity / confidence:** High / Confirmed
- **CWE / OWASP:** CWE-613; A07 Authentication Failures, A01 Broken Access Control
- **Affected:** `EnsurePlatformUser`, `EnsureCompanyUser`, user/Super Staff block actions
- **Prerequisite:** Session obtained before the account is blocked
- **Scenario:** Block actions update `account_status` only. Request middleware validates role/company but not current account status, and block actions do not delete database sessions. The stolen/current session remains usable.
- **Impact:** Revocation fails during incident response; former staff may continue viewing or mutating financial/fiscal data.
- **Isolation / financial / IRD:** Same-company access persists; permission-dependent financial/fiscal impact can be high.
- **Mitigation present:** Login rejects inactive accounts; password reset deletes sessions.
- **Recommendation:** P1—fail closed on every authenticated request and revoke all target-user sessions/remember tokens when blocked/deleted/role-changed.
- **Regression test:** Existing browser session receives 403/logout immediately after account block.
- **Blocks:** External VAPT **Yes**; IRD readiness **Yes**.

## VAPT-005 — Login endpoint lacks brute-force throttling

- **Severity / confidence:** Medium / Confirmed
- **CWE / OWASP:** CWE-307; A07 Authentication Failures
- **Affected:** `POST /login` in `routes/web.php`
- **Prerequisite:** Internet access
- **Scenario:** Unlimited credential guesses can be sent; only password-reset routes have explicit throttling.
- **Impact:** Credential stuffing/account takeover risk, including privileged and financial accounts.
- **Mitigation present:** Generic failure message, hashed passwords, strong reset password policy.
- **Recommendation:** P1—per-account/IP adaptive limiter, monitoring, safe lockout/backoff, and MFA roadmap for privileged roles.
- **Regression test:** threshold produces 429 without user enumeration.
- **Blocks:** External VAPT **Yes**; IRD readiness **Yes**.

## VAPT-006 — Raw internal exception messages reach end users

- **Severity / confidence:** Medium / Confirmed
- **CWE / OWASP:** CWE-209; A05 Security Misconfiguration
- **Affected:** multiple Sales, Purchase, payment, return, loan, income, and expense catch paths
- **Prerequisite:** Trigger an exceptional database/business path
- **Scenario:** Controllers use `with('error', $e->getMessage())`; SQLSTATE, schema, constraint, path, or internal-integrity details may be shown.
- **Impact:** Reconnaissance and sensitive implementation disclosure; inconsistent error safety.
- **Mitigation present:** Some newer paths log context and use generic messages; Laravel production debug setting can suppress uncaught details.
- **Recommendation:** P1—central exception classification with user-safe messages and redacted structured logs/correlation IDs.
- **Regression test:** forced DB exception yields generic response and sanitized log metadata.
- **Blocks:** External VAPT **Yes**; IRD readiness **No**, but remediate before review.

## VAPT-007 — Spreadsheet formula injection in Product export

- **Severity / confidence:** Medium / Confirmed
- **CWE / OWASP:** CWE-1236; A03 Injection
- **Affected:** `ProductsExport`, product names/barcodes and other exported strings
- **Prerequisite:** Ability to create/edit a product; victim opens exported XLSX
- **Scenario:** Raw Eloquent rows are exported. PhpSpreadsheet's default binder treats strings beginning `=` as formulas.
- **Impact:** Formula execution/data exfiltration or misleading financial output in a reviewer’s spreadsheet client.
- **Mitigation present:** Export is company-scoped and requires authenticated access.
- **Recommendation:** P1—explicit export mapping and safe string binding/neutralization for untrusted cells.
- **Regression test:** values beginning `=`, `+`, `-`, or `@` remain literal cells.
- **Blocks:** External VAPT **Yes**; IRD readiness **No**.

## VAPT-008 — Security header baseline is absent from repository controls

- **Severity / confidence:** Medium / High
- **CWE / OWASP:** CWE-693, CWE-1021; A05 Security Misconfiguration
- **Affected:** HTTP response pipeline/server deployment
- **Prerequisite:** Browser-based attack or framing/content injection opportunity
- **Scenario:** No app middleware/server config establishes CSP, HSTS, X-Content-Type-Options, frame protection, Referrer-Policy, or Permissions-Policy.
- **Impact:** Reduced defense-in-depth against XSS, clickjacking, MIME confusion, and transport downgrade.
- **Mitigation present:** Blade escaping, CSRF middleware, HttpOnly and SameSite=Lax session defaults.
- **Recommendation:** P1—define and test an application/server header baseline; verify Cloudflare/origin behavior in controlled infrastructure.
- **Regression test:** representative responses contain approved headers; CSP report-only rollout precedes enforcement.
- **Blocks:** External VAPT **Yes**; IRD readiness **No**.

## VAPT-009 — Confirmed vulnerable Laravel Excel version

- **Severity / confidence:** Medium / Confirmed
- **CWE / OWASP:** CWE-22 class; A06 Vulnerable Components
- **Affected:** locked `maatwebsite/excel` 3.1.69
- **Prerequisite:** Application path supplying an attacker-controlled export/store path to affected package behavior
- **Scenario:** Composer reports CVE-2026-84374 / GHSA-c7r6-vx3h-w5g2 for versions below 3.1.70. Current inspected Product export uses a constant download filename, reducing reachability of the advisory’s path-control condition.
- **Impact:** Potential filesystem write outside configured disk if a vulnerable caller-controlled path exists or is later introduced.
- **Mitigation present:** Current located export call uses `products.xlsx` constant.
- **Recommendation:** P1—upgrade to a fixed compatible version after regression review; audit all export/store call sites.
- **Regression test:** user-controlled parameters cannot determine filesystem paths.
- **Blocks:** External VAPT **Yes**; IRD readiness **No**.

## VAPT-010 — Document numbering has a concurrent collision window

- **Severity / confidence:** Medium / High
- **CWE / OWASP:** CWE-362; A04 Insecure Design
- **Affected:** `InvoiceNumberService`; Sales/Purchase create-form reservations and empty-sequence cases
- **Prerequisite:** Concurrent creates within the same company/FY
- **Scenario:** “latest row + 1” locks an existing row but cannot lock an absent first row; create forms also store an unreserved number in session. Concurrent users can receive the same number; the unique constraint prevents duplication but one transaction may fail.
- **Impact:** Availability/retry failure at billing time; no confirmed duplicate due DB uniqueness.
- **Mitigation present:** company/FY unique constraints, transactions, some row locks.
- **Recommendation:** P2—atomic per-company/FY sequence row with retry on unique/deadlock failures.
- **Regression test:** concurrent first and subsequent issuance produces unique monotonic numbers without failed user transaction.
- **Blocks:** External VAPT **No**; IRD readiness **Yes** until deterministic numbering is externally reviewed.

## VAPT-011 — Uploaded documents lack malware/content-disarm control

- **Severity / confidence:** Medium / High
- **CWE / OWASP:** CWE-509/CWE-434; A08 Integrity Failures
- **Affected:** PDF/image/document uploads and inline previews
- **Prerequisite:** Upload permission and another user opening the file
- **Scenario:** MIME/size checks do not scan active/malicious document content; some files are served inline.
- **Impact:** Malware delivery, malicious PDF behavior, and stored-content attacks against staff.
- **Mitigation present:** allowlists and size limits; CRM private storage uses restrictive MIME list and CSP on framework-served private files.
- **Recommendation:** P2—malware scanning/quarantine, download disposition for untrusted documents, image re-encoding, and content policy.
- **Regression test:** quarantined/scanner-failed files are not retrievable.
- **Blocks:** External VAPT **No**; IRD readiness **No**.

## VAPT-012 — Production cookie/debug posture depends on undeclared environment values

- **Severity / confidence:** Low / High
- **CWE / OWASP:** CWE-614; A05 Security Misconfiguration
- **Affected:** `.env.example`, session configuration, deployment controls
- **Prerequisite:** Insecure production environment configuration
- **Scenario:** example config is local/debug/HTTP and does not set secure cookies; session encryption defaults false. Repository cannot prove production overrides.
- **Impact:** Cookie transport or diagnostic exposure if deployed incorrectly.
- **Mitigation present:** HttpOnly true and SameSite=Lax defaults; environment-driven configuration.
- **Recommendation:** P2—production configuration checklist and automated fail-fast checks for HTTPS, debug off, secure cookies, trusted proxies, and environment.
- **Regression test:** production-config smoke test rejects insecure values.
- **Blocks:** External VAPT **No**, but requires infrastructure verification; IRD readiness **No**.

## VAPT-013 — Fiscal audit immutability is application-enforced, not independently tamper-evident

- **Severity / confidence:** Informational / Confirmed
- **CWE / OWASP:** CWE-749; logging/integrity observation
- **Affected:** `FiscalDocumentAuditEvent`
- **Prerequisite:** Direct database/admin compromise
- **Scenario:** model hooks reject application update/delete and restrictive FKs prevent cascade loss, but no DB trigger, hash chain, WORM store, or external log seal proves privileged-database tampering.
- **Impact:** A database administrator/attacker could alter evidence without cryptographic detection.
- **Mitigation present:** append-only model, deduplication keys, restrictive FKs, company/document scoping.
- **Recommendation:** P3—define threat/retention requirements; consider signed/hash-chained export or external append-only sink after legal review.
- **Regression test:** integrity-verification job detects altered historical event.
- **Blocks:** External VAPT **No**; IRD readiness **No**, pending IRD retention expectations.

## VAPT-014 — Backup/recovery security is not evidenced in repository

- **Severity / confidence:** Informational / Confirmed
- **CWE / OWASP:** A05 operational observation
- **Affected:** operations and disaster recovery
- **Prerequisite:** Infrastructure incident or backup compromise
- **Scenario:** No verified backup encryption, access, retention, restore-test, immutability, or secure disposal documentation was located.
- **Impact:** Unknown recoverability and backup confidentiality for financial/fiscal data.
- **Mitigation present:** None verifiable locally.
- **Recommendation:** P3—controlled infrastructure review and documented encrypted backup/restore/retention process.
- **Regression test:** scheduled restore drill and access/audit evidence.
- **Blocks:** External VAPT **No**; IRD readiness **No**, but operational sign-off required.
