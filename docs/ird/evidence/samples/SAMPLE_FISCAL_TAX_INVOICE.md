# DG ERP — SAMPLE / TEST EVIDENCE — NOT A LIVE TAX INVOICE

**Evidence ID:** EVD-INV-001

**Evidence type:** Implementation evidence derived from isolated automated fixtures and the actual Sales print view

**Approval status:** Pending IRD verification; not IRD-approved or certified

## Representative rendered information

| Field | Synthetic evidence value |
|---|---|
| Document | TAX INVOICE |
| Invoice number | SI-MIX |
| Seller | Report A |
| Seller address | Synthetic Seller Address |
| Seller PAN | Synthetic 9-digit test identifier |
| Transaction date | 2026-04-10 |
| Issue date/time | Server-issued timestamp, displayed in Asia/Kathmandu |
| Buyer | Frozen Buyer |
| Buyer address | Synthetic Buyer Address |
| Buyer PAN | Synthetic 9-digit test identifier |
| Mode of Payment | Credit |
| Print marker | Original on first successful print |

The production print view obtains seller, buyer, item, unit, payment, issue-time, and applicable HS evidence from frozen issuance fields. It does not recalculate fiscal arithmetic in the Blade.

## Canonical synthetic line evidence

| S.N. | Detail | HS Code | Qty | Unit | Unit price/gross | Discount | Fiscal net | VAT rate | VAT | Line total |
|---:|---|---|---:|---|---:|---:|---:|---:|---:|---:|
| 1 | Service 1 — VAT taxable | Not applicable | 1 | Service | 100.00 | 10.00 | 90.00 | 13% | 11.70 | 101.70 |
| 2 | Service 2 — VAT exempt | Not applicable | 1 | Service | 200.00 | 0.00 | 200.00 | 0% | 0.00 | 200.00 |
| 3 | Service 3 — zero-rated classification evidence | Not applicable | 1 | Service | 50.00 | 0.00 | 50.00 | 0% | 0.00 | 50.00 |
| 4 | Service 4 — export classification evidence | Not applicable | 1 | Service | 75.00 | 0.00 | 75.00 | 0% | 0.00 | 75.00 |
| 5 | Service 5 — out-of-scope classification evidence | Not applicable | 1 | Service | 25.00 | 0.00 | 25.00 | 0% | 0.00 | 25.00 |

| Summary | Amount |
|---|---:|
| Gross sales | 450.00 |
| Discount | 10.00 |
| Net sales | 440.00 |
| VAT | 11.70 |
| Grand total | 451.70 |

The values above are the exact canonical fixture asserted by `SalesFiscalReportTest::test_mixed_fiscal_sales_use_canonical_buckets_discounts_and_frozen_buyer`. Classification labels demonstrate implemented ERP evidence only; they are not a guessed CBMS field mapping.

## Imported-product evidence

An independent real Sales-store/print feature test issues synthetic product **Imported Exempt Device**, freezes HS code **084710**, origin **imported**, brand **Original Brand**, type **Computer**, model **X100**, size **13 inch**, unit **pcs**, quantity **1**, price **20.00**, VAT **0.00**, and total **20.00**. Forged request values are ignored. Later master-data changes do not alter Original, Reprint, or Credit Note output.

## Source trace

- `resources/views/company/sales/print.blade.php`
- `app/Services/SalesFiscalReconciliationService.php`
- `app/Services/SalesFiscalSnapshotService.php`
- `tests/Feature/SalesFiscalReportTest.php`
- `tests/Feature/NepalFiscalDocumentImmutabilityTest.php`

This Markdown is a review transcription of test-verified application behavior, not a separately invented invoice template.
