# DG ERP AI VAPT — Executive Summary

> **AI-Assisted Internal Security Assessment — Not an Independent Third-Party VAPT Certificate**

## Scope and method

Local repository-only review of DG ERP as an Internet-facing, multi-company financial/fiscal SaaS. The review traced 502 routes, authorization middleware/services, company queries, financial transactions, fiscal controls, uploads/downloads, rendered output, configuration, migrations, dependencies, and existing tests. No production, external CBMS endpoint, real credential, destructive exploit, or application modification was used.

## Verdict

**Not ready for external VAPT or IRD technical review until P0/P1 security remediation and retest.** Core database-backed tenant scoping and fiscal immutability are substantially implemented, but public-webroot file handling creates a potentially critical server-compromise path and bypasses authenticated tenant controls.

| Severity | Count |
|---|---:|
| Critical | 1 |
| High | 3 |
| Medium | 7 |
| Low | 1 |
| Informational | 2 |
| **Total** | **14** |

## Top findings

1. VAPT-001 — client extension retained for validated files written under the PHP-capable public webroot.
2. VAPT-002 — company financial/identity attachments are directly web-addressable outside authorization.
3. VAPT-003 — tracked `phpinfo()` endpoints and a public personal-information text file.
4. VAPT-004 — blocking a user does not revoke existing sessions or re-check account status per request.
5. VAPT-005 — login POST has no rate limiter.
6. VAPT-006 — internal exception messages are returned to users in multiple financial flows.
7. VAPT-007 — raw user-controlled product fields can become spreadsheet formulas on export.
8. VAPT-008 — no repository-level baseline security-header middleware/configuration.
9. VAPT-009 — locked Laravel Excel 3.1.69 matches a high-severity advisory range.
10. VAPT-010 — first-document and create-form numbering has a concurrent unique-collision window.

## Domain verdicts

- **Tenant isolation:** Application queries and tests are strong; **fail overall** because public files bypass tenant authorization.
- **Auditor:** Server-side route allowlist and permission denial are strong; public-file exposure remains an indirect confidentiality bypass.
- **Fiscal security:** **Pass with hardening observations**. Permanent evidence, return limits, print sequence, FY/reset/delete protection, transactions, and restrictive FKs are verified.
- **Authentication/session:** **Fail pending P1** due login throttling and session-revocation gaps.
- **Injection/XSS/CSRF:** No reachable SQLi, command injection, unescaped Blade XSS, or CSRF bypass confirmed. Spreadsheet formula injection is confirmed.
- **Dependencies:** One confirmed Composer advisory; npm production audit reported no vulnerabilities.

## Status

**⑨-A AI VAPT Audit = COMPLETE — READY FOR REMEDIATION**
