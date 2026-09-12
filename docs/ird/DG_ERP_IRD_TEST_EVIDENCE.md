# DG ERP — IRD Test Evidence

## Verified baseline

| Gate | Result |
|---|---|
| Compliance gate (`phpunit.compliance.xml`) | 199 tests, 2,907 assertions |
| Safe non-Journal suite | 453 tests, 5,332 assertions |

These are automated local test results, not an independent VAPT, CA opinion, IRD acceptance test, or certification.

## Coverage represented

- Authenticated company isolation and forged relationship/document rejection.
- Nepal/non-Nepal/CBMS-OFF mode matrix and permanent activation lock.
- Fiscal invoice issuance, rollback atomicity, numbering, and timestamps.
- Seller/buyer/item/unit snapshots and HS/origin rules.
- Tax classification, positive/zero VAT validation, and unresolved legacy evidence.
- Line discount, net base, VAT, mixed-tax aggregation, and canonical reconciliation.
- Frozen payment-mode mapping and resistance to later payment mutation.
- Schedule 5 presentation fields supplied from authoritative services.
- Server-controlled Original/Reprint sequencing and failed-print behavior.
- Append-only audit events and absence of false events after rollback.
- Invoice/Credit Note immutability after current CBMS setting changes.
- Full, partial, multiple partial, and exact final-residual Credit Notes.
- Fiscal Sales Account classification buckets, Credit Note subtraction, filters, NOT READY rows, and official-total exclusion.
- FY mutation/delete, factory-reset, permanent-delete, and low-level FK protection.
- Existing Sales accounting, output VAT, customer balance, COGS, stock-out, return reversal, and stock restoration.
- Historical-data safety: missing evidence remains incomplete and is not silently upgraded.
- Auditor role bootstrap, login, read surfaces, server-side mutation denial, company isolation, and assignment controls.

## Test controls

The test base requires `APP_ENV=testing`, SQLite, and `DB_DATABASE=:memory:`. Tests fail closed if a non-isolated database is resolved. `CbmsPhaseOneFoundationTest` covers supported payload mapping, readiness, formatting, response parsing, persistence, authorization, credential redaction, and a transport binding that proves no HTTP request occurs. `CbmsPhaseOneOperationalTest` adds more than 40 fake-transport cases for after-commit queueing, endpoint/network outcomes, retries, transitions, immutable attempt evidence, reconciliation, Credit Note prerequisites, authorization, and concurrency/idempotency. Real CBMS endpoints are not invoked.

Phase 1B final local run: operational 54 tests / 149 assertions; Phase 1A 8 / 70; B1+B2 10 / 72; compliance 199 / 2,924; safe non-Journal 525 / 5,640. All gates passed.
