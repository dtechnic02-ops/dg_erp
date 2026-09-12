# CBMS Payload Evidence — Blocked

**Evidence ID:** EVD-CBMS-001

> CBMS PAYLOAD EVIDENCE — BLOCKED PENDING IRD CLARIFICATION

DG ERP does not currently implement `/api/bill` or `/api/billreturn` transport. This package therefore contains no fabricated payload, acknowledgement, retry, or production-response evidence.

Authoritative clarification is still required for zero-rated and out-of-scope mapping, `total_sales`, fiscal-year/date formats, the production response envelope, duplicate handling, and endpoint-specific response codes.

References:

- `../IRD_CLARIFICATION_REGISTER.md`
- `../IRD_OFFICIAL_REFERENCE_REGISTER.md`
- `../DG_ERP_IRD_OPEN_ITEMS.md`

This expected blocker does not invalidate the implemented fiscal evidence package; it prevents unsupported CBMS claims.
