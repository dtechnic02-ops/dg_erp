# Fiscal Audit Trail — Safe Synthetic Evidence

**Evidence ID:** EVD-AUDIT-001

| Sequence | Document | Event | Representative safe metadata |
|---:|---|---|---|
| 1 | Sales Invoice | `invoice_issued` | Document number, fiscal issue time, frozen payment mode, reconciliation/readiness evidence |
| 2 | Sales Invoice | `original_printed` | Server print number 0, actor, event time |
| 3 | Sales Invoice | `reprinted` | Server print number 1, actor, event time |
| 4 | Sales Invoice | `reprinted` | Server print number 2, actor, event time |
| 5 | Protected fiscal document | blocked mutation/cancel/delete event as applicable | Actor, document identity, attempted action; no secret input |
| 6 | Original Sales Invoice | `sales_return_created` | Linked Credit Note identity and fiscal issue time |
| 7 | Sales Return | `issued` | Credit Note number and frozen fiscal evidence |

Verified properties:

- Events are append-only and company/document scoped.
- Original/Reprint numbering is derived from stored history.
- Successful issuance and Credit Note transactions record events atomically.
- Failed or rolled-back issuance creates no false event.
- Protected actions remain auditable after the current CBMS setting is forced off.
- Event evidence is protected from cascade deletion.

This document intentionally omits raw metadata dumps, credentials, passwords, request bodies, and unnecessary personal data.

Source: `app/Services/FiscalDocumentAuditService.php`, fiscal immutability, print-control, and deletion-protection tests.
