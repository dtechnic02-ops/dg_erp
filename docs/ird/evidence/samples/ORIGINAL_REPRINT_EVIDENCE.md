# Original / Reprint Control Evidence

**Evidence ID:** EVD-PRINT-001

The following sequence is produced by three actual HTTP print requests in `SalesInvoiceOriginalReprintControlTest`:

| Request | Forged request input | Rendered marker | Stored audit event | Stored print number |
|---:|---|---|---|---:|
| 1 | `copy_number=99` | Original | `original_printed` | 0 |
| 2 | `reprint_number=99` | Copy of Original (1) | `reprinted` | 1 |
| 3 | `is_original=1` | Copy of Original (2) | `reprinted` | 2 |

Verified controls:

- The server derives the sequence from append-only audit history; request copy numbers do not control it.
- Every event records the authenticated actor and event time.
- Company A print history does not affect Company B.
- Nepal CBMS-OFF and non-Nepal legacy prints remain unlabelled.
- Printing does not change invoice totals/status, accounting entries, or stock movements.
- A readiness failure does not create a false print event.

Source: `tests/Feature/SalesInvoiceOriginalReprintControlTest.php`, `app/Services/FiscalDocumentAuditService.php`.
