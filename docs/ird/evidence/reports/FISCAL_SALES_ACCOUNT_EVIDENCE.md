# Fiscal Sales Account — Synthetic Report Evidence

**Evidence ID:** EVD-RPT-001

**Purpose:** Demonstrate implemented report behavior; this is not CBMS payload evidence or an IRD-approved report format.

## Mixed fiscal invoice

| Measure | Amount |
|---|---:|
| Gross sales | 450.00 |
| Discount | 10.00 |
| Net sales | 440.00 |
| VAT-taxable sales | 90.00 |
| VAT-exempt sales | 200.00 |
| Zero-rated classification bucket | 50.00 |
| Export classification bucket | 75.00 |
| Out-of-scope classification bucket | 25.00 |
| VAT | 11.70 |
| Grand total | 451.70 |

These are exact assertions from `SalesFiscalReportTest`. The accounting builder independently reconciles revenue before tax **440.0000**, VAT **11.7000**, and grand total **451.7000**.

## Credit Note subtraction

For SI-RET, two Credit Notes (partial then final residual) subtract gross **180.00**, taxable **100.00**, exempt **80.00**, VAT **13.00**, and total **193.00**. Net totals become zero.

## Report controls

- Only permanently issued fiscal invoices and issued fiscal Credit Notes enter official totals.
- Frozen buyer identity is shown after the current customer master changes.
- Financial-year and canonical AD date filters are enforced.
- Company-scoped queries exclude another company's documents.
- A `legacy_unclassified` row remains visible as **NOT READY**, exposes readiness errors, has no official grand total, and increments `not_ready_count`.
- Reading the report creates no fiscal audit event.
- Current CBMS setting changes do not remove permanently issued fiscal documents.

The zero-rated and out-of-scope buckets are internal implemented classifications. Their CBMS mapping remains blocked pending IRD clarification.

Source: `app/Services/SalesFiscalReportService.php`, `tests/Feature/SalesFiscalReportTest.php`.
