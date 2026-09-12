# DG ERP — Software Profile

## Identification

| Field | Value |
|---|---|
| Product | DG ERP |
| Release/version identifier | [PENDING FINAL LEGAL DETAILS — release identifier not frozen] |
| Developer/provider | DGTAS — Digital Global Technology & Advanced Solutions |
| Category | Multi-company web-based ERP and accounting application |
| Listing status | NOT YET SUBMITTED; subject to IRD verification |

## Technology profile

| Layer | Repository-backed profile |
|---|---|
| Server runtime | PHP 8.2 or later |
| Application framework | Laravel 12 |
| Frontend toolchain | Vite 7, Tailwind CSS 4, JavaScript, server-rendered Blade views |
| Data access | Laravel Eloquent/query builder and database migrations |
| Database | Configurable relational connection; repository supports SQLite, MySQL/MariaDB, PostgreSQL, and SQL Server drivers. Final production database profile is PENDING FINAL LEGAL DETAILS. |
| Authentication | Laravel session authentication with active-account and company context controls |
| Authorization | Platform roles, company roles/permissions, middleware, policies/services, and server-side company scope |
| Test framework | PHPUnit 11 with isolated SQLite in-memory test databases |
| Document support | Server-rendered print views; DOMPDF dependency is present for existing PDF-capable workflows |

## Deployment architecture

DG ERP follows a conventional web architecture: authenticated browser, Laravel application, relational database, and configured session/queue/cache/mail services. Exact hosting provider, server topology, operating system, domain, IP addresses, database host, and production credentials are intentionally excluded and must be documented separately through an approved secure deployment record.

## Functional scope relevant to electronic billing

- Nepal country-gated fiscal mode with persisted company setting.
- Fiscal Sales Invoice and Sales Return/Credit Note.
- Server-generated company/financial-year-scoped numbering.
- Frozen seller, buyer, item, unit, origin, HS, payment, and issue-time evidence.
- VAT-taxable, VAT-exempt, zero-rated, export, and out-of-scope internal classifications, with unresolved historical classification kept NOT READY.
- Line discount, fiscal net base, VAT, line total, header buckets, and canonical reconciliation.
- Schedule 5-oriented Tax Invoice presentation, subject to IRD verification.
- Server-controlled Original/Reprint sequencing.
- Append-only fiscal audit trail and immutable issued-document protection.
- Fiscal Sales Account with Credit Note subtraction, filters, company isolation, and NOT READY visibility.

## Accounting and inventory integration

Sales issuance uses the existing accounting and inventory engines for revenue, output VAT, receivables/payment, COGS, and stock-out. Credit Notes use existing reversal and stock-restoration paths. Fiscal evidence reconciles with, but does not replace or redesign, those engines.

## Auditor and security controls

The dedicated Auditor role has company-scoped read access to relevant Sales, Returns, Purchases, payments, journals, ledger, fiscal report, FY, profile, and CBMS-status surfaces. Server-side controls deny mutation, settings changes, reset/deletion, custom permission elevation, and cross-company access.

Additional controls include encrypted-at-rest credential configuration fields for future CBMS use, hidden serialization, authenticated target-company resolution, immutable fiscal history, restrictive foreign keys, input validation, and isolated automated testing. No real credentials are included in this package.

## Operational items not asserted

- Final production deployment, backup, recovery, monitoring, retention, and incident-response procedures: **PENDING FINAL LEGAL DETAILS / EXTERNAL REVIEW**.
- Independent VAPT: **PENDING EXTERNAL REVIEW**.
- CA accounting/VAT review: **PENDING EXTERNAL REVIEW**.
- CBMS transport and production response handling: **PENDING WRITTEN IRD CLARIFICATION**.
